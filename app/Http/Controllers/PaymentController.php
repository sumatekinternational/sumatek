<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice, PaymentService $service)
    {
        $this->authorizeAbility('payment.manage');

        abort_if(in_array($invoice->status, ['draft', 'void'], true), 422, 'Invoice is not payable.');

        $data = $request->validate([
            'gateway' => ['required', Rule::in(['knet', 'myfatoorah', 'sadad', 'cash', 'manual'])],
            'amount' => ['required', 'integer', 'min:1', 'max:'.max(1, $invoice->balance())],
            'reference' => ['nullable', 'string'],
        ]);

        // "cash" is settled through the manual gateway.
        $gateway = $data['gateway'] === 'cash' ? 'manual' : $data['gateway'];

        ['payment' => $payment, 'result' => $result] = $service->pay(
            $invoice,
            $gateway,
            $data['amount'],
            ['reference' => $data['reference'] ?? null, 'method' => $data['gateway']],
        );

        return response()->json([
            'payment' => $payment,
            'redirect_url' => $result->redirectUrl,
            'invoice' => $invoice->fresh(),
        ], 201);
    }
}
