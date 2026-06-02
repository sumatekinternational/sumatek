<?php

namespace App\Services\BlockRegistry;

use App\Models\BlockEvent;
use App\Models\Sponsor;
use App\Models\SponsorBlock;
use App\Services\Audit\AuditLogger;
use App\Support\Pii;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Block lifecycle: block -> review -> unblock, each step append-only and
 * audit-logged (§4). Every block requires a structured reason and (per policy)
 * supporting evidence + consent capture.
 */
class BlockService
{
    public function __construct(
        protected TenantContext $tenant,
        protected AuditLogger $audit,
    ) {}

    /**
     * @param  array{reason_code:string, note?:string, evidence_path?:string}  $data
     */
    public function block(Sponsor $sponsor, array $data): SponsorBlock
    {
        $this->assertReason($data['reason_code'] ?? null);

        if (config('blockregistry.require_evidence') && empty($data['evidence_path'])) {
            throw ValidationException::withMessages([
                'evidence' => __('blockregistry.evidence_required'),
            ]);
        }

        return DB::transaction(function () use ($sponsor, $data) {
            $reviewDays = config('blockregistry.default_review_days', 365);

            // One active block per (agency, civil id): reactivate or create.
            $block = SponsorBlock::firstOrNew([
                'blocking_tenant_id' => $this->tenant->id(),
                'civil_id_hash' => Pii::hash($sponsor->civil_id),
            ]);

            $block->fill([
                'civil_id' => $sponsor->civil_id,
                'sponsor_name_ar' => $sponsor->name_ar,
                'sponsor_name_en' => $sponsor->name_en,
                'reason_code' => $data['reason_code'],
                'note' => $data['note'] ?? null,
                'evidence_path' => $data['evidence_path'] ?? $block->evidence_path,
                'status' => 'active',
                'review_at' => now()->addDays($reviewDays),
                'created_by_user_id' => Auth::id(),
                'revoked_at' => null,
                'revoked_by_user_id' => null,
                'revoked_reason' => null,
                'moderation_state' => 'none',
                'consent_captured_at' => $sponsor->consent_captured_at,
            ]);
            $block->save();

            $this->recordEvent($block, 'blocked', $data['reason_code'], $data['note'] ?? null);
            $this->audit->log('sponsor.block', $block, [
                'reason_code' => $data['reason_code'],
                'sponsor_id' => $sponsor->id,
            ]);

            return $block;
        });
    }

    public function unblock(Sponsor $sponsor, ?string $reason = null): ?SponsorBlock
    {
        $block = SponsorBlock::query()
            ->where('blocking_tenant_id', $this->tenant->id())
            ->where('civil_id_hash', Pii::hash($sponsor->civil_id))
            ->where('status', 'active')
            ->first();

        if (! $block) {
            return null;
        }

        return DB::transaction(function () use ($block, $reason) {
            $block->update([
                'status' => 'revoked',
                'revoked_at' => now(),
                'revoked_by_user_id' => Auth::id(),
                'revoked_reason' => $reason,
            ]);

            $this->recordEvent($block, 'unblocked', null, $reason);
            $this->audit->log('sponsor.unblock', $block, ['reason' => $reason]);

            return $block;
        });
    }

    protected function recordEvent(SponsorBlock $block, string $type, ?string $reasonCode, ?string $note): void
    {
        BlockEvent::create([
            'block_id' => $block->id,
            'tenant_id' => $this->tenant->id(),
            'actor_user_id' => Auth::id(),
            'type' => $type,
            'reason_code' => $reasonCode,
            'note' => $note,
            'snapshot' => $block->only(['status', 'reason_code', 'review_at', 'moderation_state']),
        ]);
    }

    protected function assertReason(?string $code): void
    {
        if (! in_array($code, config('blockregistry.reasons', []), true)) {
            throw ValidationException::withMessages([
                'reason_code' => __('blockregistry.invalid_reason'),
            ]);
        }
    }
}
