<?php

namespace App\Services\BlockRegistry;

use App\Models\SponsorBlock;
use App\Services\Audit\AuditLogger;
use App\Support\Pii;
use App\Support\TenantContext;

/**
 * FLAGSHIP — cross-agency Sponsor Eligibility / Block Check (§4).
 *
 * Given a Civil ID, queries the central registry across ALL subscribing
 * agencies and returns Clear / Caution / Blocked plus a governance-filtered
 * explanation and the mandatory legal disclaimer.
 */
class EligibilityService
{
    public function __construct(
        protected TenantContext $tenant,
        protected AuditLogger $audit,
    ) {}

    public function check(string $civilId): array
    {
        $hash = Pii::hash($civilId);

        // The registry is cross-tenant by design; bypass tenant scoping for the
        // count, but never expose other tenants' identities beyond policy.
        $blocks = $this->tenant->bypass(fn () => SponsorBlock::query()
            ->where('civil_id_hash', $hash)
            ->where('status', 'active')
            ->where('moderation_state', '!=', 'removed')
            ->get());

        $count = $blocks->count();
        $agencyCount = $blocks->pluck('blocking_tenant_id')->unique()->count();

        $status = $this->status($count);

        $result = [
            'civil_id_masked' => $this->mask($civilId),
            'status' => $status,                 // clear | caution | blocked
            'blocked_by_agencies' => $agencyCount,
            'active_blocks' => $count,
            'blocked_by_this_agency' => $blocks
                ->contains('blocking_tenant_id', $this->tenant->id()),
            'prompt' => __("blockregistry.prompt.$status"),
            'details' => $this->details($blocks),
            'disclaimer' => __(config('blockregistry.disclaimer_key')),
            'checked_at' => now()->toIso8601String(),
        ];

        $this->audit->log('eligibility.check', null, [
            'subject_hash' => $hash,
            'status' => $status,
            'active_blocks' => $count,
        ]);

        return $result;
    }

    protected function status(int $count): string
    {
        if ($count >= config('blockregistry.blocked_threshold', 1)) {
            return 'blocked';
        }

        if ($count >= config('blockregistry.caution_threshold', 1)) {
            return 'caution';
        }

        return 'clear';
    }

    /**
     * Filter what other agencies may see per the governance policy (§4).
     */
    protected function details($blocks): array
    {
        $visibility = config('blockregistry.visibility', 'anonymized');
        $myTenantId = $this->tenant->id();

        return $blocks->map(function (SponsorBlock $block) use ($visibility, $myTenantId) {
            $mine = $block->blocking_tenant_id === $myTenantId;

            // An agency always sees its own block in full.
            if ($mine || $visibility === 'full') {
                return [
                    'agency' => optional($block->blockingTenant)->name_en,
                    'reason_code' => $block->reason_code,
                    'note' => $mine ? $block->note : null,
                    'created_at' => $block->created_at?->toIso8601String(),
                    'is_yours' => $mine,
                ];
            }

            if ($visibility === 'anonymized') {
                return [
                    'agency' => null,
                    'reason_code' => $block->reason_code,
                    'created_at' => $block->created_at?->toDateString(),
                    'is_yours' => false,
                ];
            }

            // vendor_mediated: reveal nothing but the existence of a block.
            return [
                'agency' => null,
                'reason_code' => null,
                'is_yours' => false,
                'contact' => __('blockregistry.contact_vendor'),
            ];
        })->values()->all();
    }

    protected function mask(string $civilId): string
    {
        $digits = preg_replace('/\D/', '', $civilId) ?? '';

        return strlen($digits) <= 4
            ? str_repeat('•', strlen($digits))
            : str_repeat('•', strlen($digits) - 4).substr($digits, -4);
    }
}
