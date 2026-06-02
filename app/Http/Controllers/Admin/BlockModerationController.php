<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockEvent;
use App\Models\SponsorBlock;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Vendor governance / moderation layer for the block registry (§4). Lets the
 * vendor remove abusive or unsupported blocks and uphold disputed ones.
 */
class BlockModerationController extends Controller
{
    public function index(Request $request)
    {
        $blocks = SponsorBlock::query()
            ->with('blockingTenant:id,name_en,name_ar')
            ->when($request->string('state')->toString(), fn ($q, $s) => $q->where('moderation_state', $s))
            ->when($request->boolean('flagged_only'), fn ($q) => $q->where('moderation_state', 'flagged'))
            ->latest()
            ->paginate(30);

        return response()->json($blocks);
    }

    public function revoke(Request $request, SponsorBlock $block, AuditLogger $audit)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);

        DB::transaction(function () use ($block, $data, $audit) {
            $block->update([
                'status' => 'revoked',
                'moderation_state' => 'removed',
                'moderated_by_user_id' => Auth::id(),
                'revoked_at' => now(),
                'revoked_reason' => $data['reason'],
            ]);

            BlockEvent::create([
                'block_id' => $block->id,
                'tenant_id' => $block->blocking_tenant_id,
                'actor_user_id' => Auth::id(),
                'type' => 'removed',
                'note' => $data['reason'],
                'snapshot' => $block->only(['status', 'moderation_state']),
            ]);

            $audit->log('moderation.block_removed', $block, ['reason' => $data['reason']]);
        });

        return response()->json(['message' => 'Block removed by vendor moderation.']);
    }

    public function uphold(SponsorBlock $block, AuditLogger $audit)
    {
        $block->update(['moderation_state' => 'upheld', 'moderated_by_user_id' => Auth::id()]);

        BlockEvent::create([
            'block_id' => $block->id,
            'tenant_id' => $block->blocking_tenant_id,
            'actor_user_id' => Auth::id(),
            'type' => 'upheld',
            'snapshot' => $block->only(['status', 'moderation_state']),
        ]);

        $audit->log('moderation.block_upheld', $block);

        return response()->json(['message' => 'Block upheld.']);
    }
}
