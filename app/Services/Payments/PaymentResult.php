<?php

namespace App\Services\Payments;

/** Normalised outcome of a gateway charge/verify call (§6.5). */
class PaymentResult
{
    public function __construct(
        public string $status,            // captured | pending | failed
        public ?string $reference = null,
        public ?string $redirectUrl = null, // hosted-payment-page URL when pending
        public array $meta = [],
    ) {}

    public function captured(): bool
    {
        return $this->status === 'captured';
    }
}
