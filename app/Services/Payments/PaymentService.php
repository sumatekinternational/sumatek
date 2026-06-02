<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Records payments against invoices through the resolved gateway and keeps the
 * invoice balance/status reconciled (§6.5).
 */
class PaymentService
{
    public function __construct(
        protected PaymentGatewayManager $gateways,
        protected AuditLogger $audit,
    ) {}

    /**
     * @return array{payment: Payment, result: PaymentResult}
     */
    public function pay(Invoice $invoice, string $gateway, int $amountFils, array $options = []): array
    {
        $result = $this->gateways->driver($gateway)->charge($invoice, $amountFils, $options);

        $payment = DB::transaction(function () use ($invoice, $gateway, $amountFils, $result) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'gateway' => $gateway,
                'reference' => $result->reference,
                'amount' => $amountFils,
                'status' => $result->captured() ? 'captured' : 'pending',
                'paid_at' => $result->captured() ? now() : null,
                'meta' => $result->meta,
            ]);

            if ($result->captured()) {
                $invoice->recalculate();
            }

            return $payment;
        });

        $this->audit->log('payment.recorded', $payment, [
            'invoice_id' => $invoice->id,
            'gateway' => $gateway,
            'amount' => $amountFils,
            'status' => $payment->status,
        ]);

        return ['payment' => $payment, 'result' => $result];
    }
}
