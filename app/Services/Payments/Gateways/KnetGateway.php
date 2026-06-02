<?php

namespace App\Services\Payments\Gateways;

use App\Models\Invoice;
use App\Services\Payments\PaymentGatewayInterface;
use App\Services\Payments\PaymentResult;
use RuntimeException;

/**
 * KNET gateway (§6.5). Integration seam: returns a pending result with a hosted
 * payment-page URL; settlement is confirmed via verify()/callback. Wire the
 * Tranportal request once merchant credentials are provisioned.
 */
class KnetGateway implements PaymentGatewayInterface
{
    public function charge(Invoice $invoice, int $amountFils, array $options = []): PaymentResult
    {
        $config = config('payments.gateways.knet');

        if (empty($config['tranportal_id']) || empty($config['base_url'])) {
            throw new RuntimeException('KNET gateway is not configured.');
        }

        // TODO: build & sign the Tranportal payment-init request, then return
        // the redirect URL KNET responds with.
        throw new RuntimeException('KNET charge not yet implemented — pending merchant onboarding.');
    }

    public function verify(string $reference): PaymentResult
    {
        throw new RuntimeException('KNET verify not yet implemented — pending merchant onboarding.');
    }

    public function key(): string
    {
        return 'knet';
    }
}
