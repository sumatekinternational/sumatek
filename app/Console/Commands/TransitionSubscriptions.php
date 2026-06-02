<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

/**
 * Moves tenants through the subscription lifecycle: active -> grace ->
 * suspended once the paid period and grace window have elapsed (§2).
 */
class TransitionSubscriptions extends Command
{
    protected $signature = 'subscriptions:transition';

    protected $description = 'Suspend tenants whose subscription has lapsed past the grace window.';

    public function handle(): int
    {
        $suspended = 0;

        Tenant::where('status', 'active')->with('activeSubscription')->chunkById(200, function ($tenants) use (&$suspended) {
            foreach ($tenants as $tenant) {
                if ($tenant->subscriptionStatus() === 'suspended') {
                    $tenant->update(['status' => 'suspended']);
                    $suspended++;
                }
            }
        });

        $this->info("Suspended {$suspended} tenant(s) past grace.");

        return self::SUCCESS;
    }
}
