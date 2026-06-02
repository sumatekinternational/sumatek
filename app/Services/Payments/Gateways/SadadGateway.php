<?php

namespace App\Services\Payments\Gateways;

use App\Models\Invoice;
use App\Services\Payments\PaymentGatewayInterface;
use App\Services\Payments\PaymentResult;
use Illuminate\Support\Str;

/**
 * SADAD reference capture (§6.5). SADAD government fees are typically paid and
 * reconciled out-of-band; this records the reference and marks it captured once
 * the agency confirms settlement.
 */
class SadadGateway implements PaymentGatewayInterface
{
    public function charge(Invoice $invoice, int $amountFils, array $options = []): PaymentResult
    {
        return new PaymentResult(
            status: $options['confirmed'] ?? false ? 'captured' : 'pending',
            reference: $options['reference'] ?? 'SADAD-'.Str::upper(Str::random(8)),
        );
    }

    public function verify(string $reference): PaymentResult
    {
        return new PaymentResult('captured', $reference);
    }

    public function key(): string
    {
        return 'sadad';
    }
}
