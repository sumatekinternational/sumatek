<?php

namespace App\Services\Contracts;

use App\Models\Contract;
use App\Services\Audit\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;

/**
 * Creates and progresses PAM-standard contracts, including the six-month
 * warranty computation and recruitment-fee refund tracking (§6.3).
 */
class ContractService
{
    public function __construct(
        protected TenantContext $tenant,
        protected AuditLogger $audit,
    ) {}

    public function create(array $data): Contract
    {
        $data['contract_no'] ??= $this->nextContractNumber();

        $contract = Contract::create($data);

        $this->audit->log('contract.created', $contract, ['contract_no' => $contract->contract_no]);

        return $contract;
    }

    /** Record a signature for one party; e-signature path optional. */
    public function sign(Contract $contract, string $party, ?string $signaturePath = null): Contract
    {
        abort_unless(in_array($party, ['sponsor', 'worker'], true), 422, 'Invalid signing party.');

        $contract->forceFill([
            "signed_by_{$party}_at" => now(),
            "{$party}_signature_path" => $signaturePath,
        ])->save();

        $this->audit->log('contract.signed', $contract, ['party' => $party]);

        return $contract;
    }

    /**
     * Activate a (fully signed) contract: set the period and start the warranty
     * clock from the start date.
     */
    public function activate(Contract $contract, ?Carbon $startDate = null): Contract
    {
        $start = $startDate ?? $contract->start_date ?? now();

        $contract->fill([
            'status' => 'active',
            'start_date' => $start,
            'end_date' => $start->copy()->addMonths($contract->duration_months),
            'warranty_ends_at' => $start->copy()->addMonths($contract->warranty_months),
        ])->save();

        $this->audit->log('contract.activated', $contract, [
            'warranty_ends_at' => $contract->warranty_ends_at?->toDateString(),
        ]);

        return $contract;
    }

    /**
     * Record a refund of recruitment expense (e.g. worker discontinued inside
     * the warranty window).
     */
    public function refund(Contract $contract, int $amountFils, ?string $reason = null): Contract
    {
        $contract->refunded_amount = min($contract->recruitment_fee, $contract->refunded_amount + $amountFils);
        $contract->save();

        $this->audit->log('contract.refunded', $contract, ['amount' => $amountFils, 'reason' => $reason]);

        return $contract;
    }

    protected function nextContractNumber(): string
    {
        $seq = Contract::withTrashed()->count() + 1;

        return sprintf('C%s-%05d', now()->year, $seq);
    }
}
