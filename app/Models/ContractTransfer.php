<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tanazul (sponsorship transfer) record (§6.3). */
class ContractTransfer extends Model
{
    use BelongsToTenant, HasFactory;

    public const STATUSES = ['requested', 'office_review', 'pam_submitted', 'approved', 'rejected', 'completed'];

    protected $fillable = [
        'tenant_id', 'contract_id', 'worker_id', 'from_sponsor_id', 'to_sponsor_id',
        'status', 'authorized', 'pam_reference', 'transfer_fee',
        'requested_by_user_id', 'note', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'authorized' => 'boolean',
            'transfer_fee' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
