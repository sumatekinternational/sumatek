<?php

namespace App\Services\Payments\Gateways;

use App\Models\Invoice;
use App\Services\Payments\PaymentGatewayInterface;
use App\Services\Payments\PaymentResult;
use Illuminate\Support\Str;

/**
 * Manual / cash settlement (§6.5). Records the payment as captured immediately
 * — used for over-the-counter cash or externally-reconciled SADAD.
 */
class ManualGateway implements PaymentGatewayInterface
{
    public function charge(Invoice $invoice, int $amountFils, array $options = []): PaymentResult
    {
        return new PaymentResult(
            status: 'captured',
            reference: $options['reference'] ?? 'MAN-'.Str::upper(Str::random(10)),
            meta: ['method' => $options['method'] ?? 'cash'],
        );
    }

    public function verify(string $reference): PaymentResult
    {
        return new PaymentResult('captured', $reference);
    }

    public function key(): string
    {
        return 'manual';
    }
}
