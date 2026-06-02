<?php

namespace App\Services\BlockRegistry;

use App\Models\BlockDispute;
use App\Models\BlockEvent;
use App\Models\SponsorBlock;
use App\Services\Audit\AuditLogger;
use App\Support\Pii;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Block dispute workflow (§4). An agency raises a dispute (on its own behalf or
 * a sponsor's); raising a dispute flags the block for vendor moderation. The
 * vendor resolves by upholding, removing, or rejecting.
 */
class DisputeService
{
    public function __construct(
        protected TenantContext $tenant,
        protected AuditLogger $audit,
    ) {}

    /**
     * Raise a dispute against a specific block, or against every active block
     * for a Civil ID (the walk-in sponsor case).
     *
     * @return Collection<int,BlockDispute>
     */
    public function raise(array $data): Collection
    {
        $blocks = $this->tenant->bypass(function () use ($data) {
            if (! empty($data['block_id'])) {
                return SponsorBlock::where('id', $data['block_id'])->get();
            }

            return SponsorBlock::where('civil_id_hash', Pii::hash($data['civil_id']))
                ->where('status', 'active')->get();
        });

        abort_if($blocks->isEmpty(), 404, __('blockregistry.no_active_block'));

        return DB::transaction(fn () => $blocks->map(function (SponsorBlock $block) use ($data) {
            $dispute = BlockDispute::create([
                'block_id' => $block->id,
                'tenant_id' => $this->tenant->id(),
                'raised_by_user_id' => Auth::id(),
                'disputant_type' => $data['disputant_type'] ?? 'agency',
                'reason' => $data['reason'],
                'status' => 'open',
            ]);

            $block->update(['moderation_state' => 'flagged']);
            $this->audit->log('dispute.raised', $dispute, ['block_id' => $block->id]);

            return $dispute;
        }));
    }

    /** Vendor resolution: uphold | remove | reject. */
    public function resolve(BlockDispute $dispute, string $action, ?string $note = null): BlockDispute
    {
        return DB::transaction(function () use ($dispute, $action, $note) {
            $block = $dispute->block;

            match ($action) {
                'uphold' => $this->uphold($dispute, $block),
                'remove' => $this->remove($dispute, $block, $note),
                'reject' => $this->markRejected($dispute, $block),
                default => abort(422, 'Invalid resolution action.'),
            };

            $dispute->update([
                'resolution_note' => $note,
                'resolved_by_user_id' => Auth::id(),
                'resolved_at' => now(),
            ]);

            $this->audit->log('dispute.resolved', $dispute, ['action' => $action]);

            return $dispute;
        });
    }

    protected function uphold(BlockDispute $dispute, SponsorBlock $block): void
    {
        $block->update(['moderation_state' => 'upheld']);
        $dispute->status = 'resolved_upheld';
    }

    protected function remove(BlockDispute $dispute, SponsorBlock $block, ?string $note): void
    {
        $block->update([
            'status' => 'revoked',
            'moderation_state' => 'removed',
            'revoked_at' => now(),
            'revoked_by_user_id' => Auth::id(),
            'revoked_reason' => $note ?? 'dispute_resolved',
        ]);

        BlockEvent::create([
            'block_id' => $block->id,
            'tenant_id' => $block->blocking_tenant_id,
            'actor_user_id' => Auth::id(),
            'type' => 'removed',
            'note' => $note,
            'snapshot' => $block->only(['status', 'moderation_state']),
        ]);

        $dispute->status = 'resolved_removed';
    }

    protected function markRejected(BlockDispute $dispute, SponsorBlock $block): void
    {
        // Block stands; clear the flag so it no longer appears in the queue.
        $block->update(['moderation_state' => 'upheld']);
        $dispute->status = 'rejected';
    }
}
