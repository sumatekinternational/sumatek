<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\SubscriptionRenewalReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Fires renewal reminders at 60/30/7/1 days before expiry (§2/§6.7), delivered
 * in-app + email + WhatsApp to each agency's admin/owner users.
 */
class SendRenewalReminders extends Command
{
    protected $signature = 'subscriptions:remind';

    protected $description = 'Notify tenants of upcoming subscription renewals.';

    public function handle(): int
    {
        $windows = config('subscription.reminder_days', [60, 30, 7, 1]);
        $sent = 0;

        foreach ($windows as $days) {
            $target = now()->addDays($days)->toDateString();

            Subscription::where('status', 'active')
                ->whereDate('ends_at', $target)
                ->with('tenant.users')
                ->chunkById(200, function ($subscriptions) use (&$sent, $days) {
                    foreach ($subscriptions as $subscription) {
                        $recipients = $subscription->tenant?->users ?? collect();

                        if ($recipients->isNotEmpty()) {
                            Notification::send($recipients, new SubscriptionRenewalReminder($subscription, $days));
                            $sent += $recipients->count();
                        }
                    }
                });
        }

        $this->info("Dispatched {$sent} renewal reminder(s).");

        return self::SUCCESS;
    }
}
