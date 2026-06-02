<?php

namespace App\Notifications;

use App\Models\Subscription;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\Messages\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Renewal reminder fired at 60/30/7/1 days before expiry (§2/§6.7), delivered
 * in-app (database), by email and over WhatsApp.
 */
class SubscriptionRenewalReminder extends Notification
{
    use Queueable;

    public function __construct(
        public Subscription $subscription,
        public int $daysLeft,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', WhatsAppChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.renewal.subject', ['days' => $this->daysLeft]))
            ->line(__('notifications.renewal.line', [
                'days' => $this->daysLeft,
                'date' => $this->subscription->ends_at->toDateString(),
            ]));
    }

    public function toWhatsapp(object $notifiable): WhatsAppMessage
    {
        return WhatsAppMessage::make(__('notifications.renewal.line', [
            'days' => $this->daysLeft,
            'date' => $this->subscription->ends_at->toDateString(),
        ]));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription_renewal',
            'subscription_id' => $this->subscription->id,
            'days_left' => $this->daysLeft,
            'ends_at' => $this->subscription->ends_at->toIso8601String(),
        ];
    }
}
