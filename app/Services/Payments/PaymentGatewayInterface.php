<?php

namespace App\Services\Payments;

use App\Models\Invoice;

/**
 * Contract for payment gateways (§6.5). Implementations are pluggable and
 * resolved through PaymentGatewayManager.
 */
interface PaymentGatewayInterface
{
    /**
     * Begin a charge for the given amount (fils) against an invoice.
     * Returns captured (settled now, e.g. manual/cash) or pending (with a
     * redirectUrl to a hosted payment page for KNET/card).
     */
    public function charge(Invoice $invoice, int $amountFils, array $options = []): PaymentResult;

    /** Verify/settle a previously initiated charge by gateway reference. */
    public function verify(string $reference): PaymentResult;

    public function key(): string;
}
