<?php

namespace App\Notifications\Messages;

/** Simple payload for a WhatsApp Business API text message (§6.7). */
class WhatsAppMessage
{
    public function __construct(public string $body) {}

    public static function make(string $body): self
    {
        return new self($body);
    }
}
