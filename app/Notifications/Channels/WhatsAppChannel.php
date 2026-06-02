<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Business API notification channel (§6.7). Integration seam: when no
 * token is configured it logs instead of sending, so the rest of the
 * notification pipeline works in dev and tests.
 */
class WhatsAppChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsapp')) {
            return;
        }

        $to = $notifiable->routeNotificationFor('whatsapp', $notification);
        if (! $to) {
            return;
        }

        $message = $notification->toWhatsapp($notifiable);
        $config = config('services.whatsapp');

        if (empty($config['token']) || empty($config['phone_number_id'])) {
            Log::info('WhatsApp (not configured) — would send', ['to' => $to, 'body' => $message->body]);

            return;
        }

        Http::withToken($config['token'])
            ->post("https://graph.facebook.com/v20.0/{$config['phone_number_id']}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => $message->body],
            ]);
    }
}
