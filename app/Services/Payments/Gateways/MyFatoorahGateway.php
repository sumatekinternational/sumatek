<?php

namespace App\Services\Payments\Gateways;

use App\Models\Invoice;
use App\Services\Payments\PaymentGatewayInterface;
use App\Services\Payments\PaymentResult;
use RuntimeException;

/**
 * MyFatoorah card/aggregator gateway (§6.5). Integration seam: returns a
 * pending result with the hosted invoice URL; settlement confirmed via
 * verify()/webhook once the API token is provisioned.
 */
class MyFatoorahGateway implements PaymentGatewayInterface
{
    public function charge(Invoice $invoice, int $amountFils, array $options = []): PaymentResult
    {
        $config = config('payments.gateways.myfatoorah');

        if (empty($config['token'])) {
            throw new RuntimeException('MyFatoorah gateway is not configured.');
        }

        // TODO: call SendPayment / ExecutePayment and return InvoiceURL.
        throw new RuntimeException('MyFatoorah charge not yet implemented — pending API token.');
    }

    public function verify(string $reference): PaymentResult
    {
        throw new RuntimeException('MyFatoorah verify not yet implemented — pending API token.');
    }

    public function key(): string
    {
        return 'myfatoorah';
    }
}
