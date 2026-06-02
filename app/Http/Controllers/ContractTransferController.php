<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractTransfer;
use App\Services\Contracts\TransferService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContractTransferController extends Controller
{
    public function store(Request $request, Contract $contract, TransferService $service)
    {
        $this->authorizeAbility('contract.manage');

        $data = $request->validate([
            'to_sponsor_id' => ['required', Rule::exists('sponsors', 'id')],
            'authorized' => ['nullable', 'boolean'],
            'transfer_fee' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string'],
        ]);

        return response()->json($service->request($contract, $data), 201);
    }

    public function advance(Request $request, ContractTransfer $transfer, TransferService $service)
    {
        $this->authorizeAbility('contract.manage');

        $data = $request->validate([
            'status' => ['required', Rule::in(ContractTransfer::STATUSES)],
            'pam_reference' => ['nullable', 'string'],
        ]);

        return response()->json($service->advance($transfer, $data['status'], $data));
    }
}
