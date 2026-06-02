<?php

namespace App\Services\Contracts;

use App\Models\Contract;
use App\Models\ContractTransfer;
use App\Services\Audit\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Tanazul (sponsorship transfer) workflow (§6.3). An *unauthorised* transfer
 * voids the contract warranty.
 */
class TransferService
{
    public function __construct(
        protected TenantContext $tenant,
        protected AuditLogger $audit,
    ) {}

    public function request(Contract $contract, array $data): ContractTransfer
    {
        $transfer = ContractTransfer::create([
            'contract_id' => $contract->id,
            'worker_id' => $contract->worker_id,
            'from_sponsor_id' => $contract->sponsor_id,
            'to_sponsor_id' => $data['to_sponsor_id'],
            'status' => 'requested',
            'authorized' => $data['authorized'] ?? true,
            'transfer_fee' => $data['transfer_fee'] ?? 0,
            'requested_by_user_id' => Auth::id(),
            'note' => $data['note'] ?? null,
        ]);

        $this->audit->log('transfer.requested', $transfer, ['contract_id' => $contract->id]);

        return $transfer;
    }

    /** Advance the transfer through its workflow states. */
    public function advance(ContractTransfer $transfer, string $status, array $data = []): ContractTransfer
    {
        if (! in_array($status, ContractTransfer::STATUSES, true)) {
            throw ValidationException::withMessages(['status' => 'Invalid transfer status.']);
        }

        return DB::transaction(function () use ($transfer, $status, $data) {
            $transfer->status = $status;
            if (! empty($data['pam_reference'])) {
                $transfer->pam_reference = $data['pam_reference'];
            }

            if ($status === 'completed') {
                $transfer->completed_at = now();
                $this->complete($transfer);
            }

            $transfer->save();
            $this->audit->log('transfer.advanced', $transfer, ['status' => $status]);

            return $transfer;
        });
    }

    /**
     * On completion: move the worker to the new sponsor, close the old
     * contract, and void the warranty if the transfer was unauthorised.
     */
    protected function complete(ContractTransfer $transfer): void
    {
        $contract = $transfer->contract;
        $contract->status = 'transferred';

        if (! $transfer->authorized) {
            $contract->warranty_void = true;
            $contract->warranty_void_reason = 'unauthorized_transfer';
        }

        $contract->save();
    }
}
