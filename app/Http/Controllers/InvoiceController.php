<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAbility('invoice.view');

        $invoices = Invoice::query()
            ->with('sponsor:id,name_ar,name_en')
            ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json($invoices);
    }

    public function store(Request $request)
    {
        $this->authorizeAbility('invoice.manage');

        $data = $request->validate([
            // WPS awareness (§6.5): invoices are raised against sponsors only.
            'sponsor_id' => ['required', Rule::exists('sponsors', 'id')],
            'contract_id' => ['nullable', Rule::exists('contracts', 'id')],
            'due_at' => ['nullable', 'date'],
            'tax' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description_en' => ['required', 'string'],
            'items.*.description_ar' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
        ]);

        $invoice = DB::transaction(function () use ($data) {
            $invoice = Invoice::create([
                'sponsor_id' => $data['sponsor_id'],
                'contract_id' => $data['contract_id'] ?? null,
                'invoice_no' => 'INV'.now()->format('Y').'-'.Str::upper(Str::random(6)),
                'status' => 'draft',
                'tax' => $data['tax'] ?? 0,
                'due_at' => $data['due_at'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $invoice->items()->create($item);
            }

            $invoice->recalculate();

            return $invoice;
        });

        return response()->json($invoice->load('items'), 201);
    }

    public function show(Invoice $invoice)
    {
        $this->authorizeAbility('invoice.view');

        return response()->json($invoice->load('items', 'payments', 'sponsor'));
    }

    /** Issue a draft invoice (locks it for payment). */
    public function issue(Invoice $invoice)
    {
        $this->authorizeAbility('invoice.manage');

        abort_unless($invoice->status === 'draft', 422, 'Only draft invoices can be issued.');

        $invoice->update(['status' => 'issued', 'issued_at' => now()]);
        $invoice->reconcileStatus();
        $invoice->save();

        return response()->json($invoice);
    }

    public function void(Invoice $invoice)
    {
        $this->authorizeAbility('invoice.manage');

        $invoice->update(['status' => 'void']);

        return response()->json($invoice);
    }
}
