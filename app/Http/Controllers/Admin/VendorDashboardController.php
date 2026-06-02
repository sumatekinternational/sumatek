<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SponsorBlock;
use App\Models\Subscription;
use App\Models\Tenant;

/**
 * Vendor dashboard (§6.8): tenants, MRR/ARR, renewals at risk, registry growth.
 * Operates across all tenants (control plane).
 */
class VendorDashboardController extends Controller
{
    public function __invoke()
    {
        $activeSubs = Subscription::where('status', 'active')->where('ends_at', '>=', now());

        $arr = (int) (clone $activeSubs)->sum('amount_paid'); // KWD fils / year

        return response()->json([
            'tenants' => [
                'total' => Tenant::count(),
                'active' => Tenant::where('status', 'active')->count(),
                'suspended' => Tenant::where('status', 'suspended')->count(),
            ],
            'subscriptions' => [
                'active' => (clone $activeSubs)->count(),
                'arr_fils' => $arr,
                'mrr_fils' => intdiv($arr, 12),
            ],
            'renewals_at_risk_30d' => Subscription::where('status', 'active')
                ->whereBetween('ends_at', [now(), now()->addDays(30)])->count(),
            'block_registry' => [
                'total_blocks' => SponsorBlock::count(),
                'active_blocks' => SponsorBlock::where('status', 'active')->count(),
                'flagged_for_moderation' => SponsorBlock::where('moderation_state', 'flagged')->count(),
            ],
        ]);
    }
}
