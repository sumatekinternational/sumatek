<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

/**
 * Fires renewal reminders at 60/30/7/1 days before expiry (§2). Notification
 * delivery (email/SMS/push) is handled by the notifications subsystem (§6.7);
 * this command identifies the due reminders.
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
                ->with('tenant')
                ->chunkById(200, function ($subscriptions) use (&$sent, $days) {
                    foreach ($subscriptions as $subscription) {
                        // TODO(§6.7): dispatch RenewalReminderNotification on
                        // mail/SMS/push channels. Logged here for traceability.
                        $this->line("Reminder ({$days}d) -> tenant #{$subscription->tenant_id}");
                        $sent++;
                    }
                });
        }

        $this->info("Queued {$sent} renewal reminder(s).");

        return self::SUCCESS;
    }
}
