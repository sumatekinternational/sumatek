<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PAM-standard domestic-worker contract (§6.3) with warranty + guarantee
 * tracking.
 */
class Contract extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    public const STATUSES = ['draft', 'active', 'completed', 'terminated', 'transferred'];

    protected $fillable = [
        'tenant_id', 'sponsor_id', 'worker_id', 'contract_no', 'type', 'status',
        'monthly_salary', 'currency', 'duration_months', 'start_date', 'end_date',
        'warranty_months', 'warranty_ends_at', 'warranty_void', 'warranty_void_reason',
        'replacement_months', 'insurance_years',
        'recruitment_fee', 'refunded_amount',
        'signed_by_sponsor_at', 'signed_by_worker_at',
        'sponsor_signature_path', 'worker_signature_path',
        'notarized_at', 'notary_reference', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'warranty_ends_at' => 'date',
            'warranty_void' => 'boolean',
            'signed_by_sponsor_at' => 'datetime',
            'signed_by_worker_at' => 'datetime',
            'notarized_at' => 'datetime',
            'monthly_salary' => 'integer',
            'duration_months' => 'integer',
            'warranty_months' => 'integer',
            'replacement_months' => 'integer',
            'insurance_years' => 'integer',
            'recruitment_fee' => 'integer',
            'refunded_amount' => 'integer',
        ];
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(ContractTransfer::class);
    }

    public function isFullySigned(): bool
    {
        return $this->signed_by_sponsor_at && $this->signed_by_worker_at;
    }

    /** Is the contract still inside its (non-void) warranty window? */
    public function isUnderWarranty(): bool
    {
        return ! $this->warranty_void
            && $this->warranty_ends_at
            && $this->warranty_ends_at->endOfDay()->isFuture();
    }
}
