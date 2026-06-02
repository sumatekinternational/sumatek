<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Services\Contracts\ContractService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAbility('contract.view');

        $contracts = Contract::query()
            ->with(['sponsor:id,name_ar,name_en', 'worker:id,name_en,nationality'])
            ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json($contracts);
    }

    public function store(Request $request, ContractService $service)
    {
        $this->authorizeAbility('contract.manage');

        $data = $request->validate([
            'sponsor_id' => ['required', Rule::exists('sponsors', 'id')],
            'worker_id' => ['required', Rule::exists('workers', 'id')],
            'type' => ['nullable', 'string'],
            'monthly_salary' => ['required', 'integer', 'min:0'],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:60'],
            'warranty_months' => ['nullable', 'integer', 'min:0', 'max:24'],
            'replacement_months' => ['nullable', 'integer', 'min:0', 'max:24'],
            'insurance_years' => ['nullable', 'integer', 'min:0', 'max:10'],
            'recruitment_fee' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        return response()->json($service->create($data), 201);
    }

    public function show(Contract $contract)
    {
        $this->authorizeAbility('contract.view');

        return response()->json($contract->load('sponsor', 'worker', 'transfers'));
    }

    public function update(Request $request, Contract $contract)
    {
        $this->authorizeAbility('contract.manage');

        $contract->update($request->validate([
            'monthly_salary' => ['sometimes', 'integer', 'min:0'],
            'recruitment_fee' => ['sometimes', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'notary_reference' => ['nullable', 'string'],
        ]));

        return response()->json($contract);
    }

    public function sign(Request $request, Contract $contract, ContractService $service)
    {
        $this->authorizeAbility('contract.manage');

        $data = $request->validate([
            'party' => ['required', Rule::in(['sponsor', 'worker'])],
            'signature' => ['nullable', 'file', 'max:4096', 'mimes:png,jpg,jpeg,pdf'],
        ]);

        $path = $request->hasFile('signature')
            ? $request->file('signature')->store("signatures/{$contract->id}", 'local')
            : null;

        return response()->json($service->sign($contract, $data['party'], $path));
    }

    public function activate(Request $request, Contract $contract, ContractService $service)
    {
        $this->authorizeAbility('contract.manage');

        abort_unless($contract->isFullySigned(), 422, 'Both parties must sign before activation.');

        $data = $request->validate(['start_date' => ['nullable', 'date']]);
        $start = isset($data['start_date']) ? Carbon::parse($data['start_date']) : null;

        return response()->json($service->activate($contract, $start));
    }

    public function refund(Request $request, Contract $contract, ContractService $service)
    {
        $this->authorizeAbility('contract.manage');

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string'],
        ]);

        return response()->json($service->refund($contract, $data['amount'], $data['reason'] ?? null));
    }
}
