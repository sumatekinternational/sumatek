<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Visa 20 deployment pipeline case (§6.4). The ordered STAGES drive the Kanban
 * board and the per-stage SLA / document checklist.
 */
class VisaCase extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    /** Canonical ordered pipeline stages. */
    public const STAGES = [
        'intake',
        'documents',
        'sadad_fee',
        'medical',
        'visa_issued',
        'travel',
        'arrival',
        'orientation',
        'deployed',
    ];

    /** Default SLA (days) allowed in each stage before it is breached. */
    public const STAGE_SLA_DAYS = [
        'intake' => 3,
        'documents' => 7,
        'sadad_fee' => 3,
        'medical' => 7,
        'visa_issued' => 14,
        'travel' => 14,
        'arrival' => 2,
        'orientation' => 3,
        'deployed' => 0,
    ];

    protected $fillable = [
        'tenant_id', 'worker_id', 'sponsor_id', 'contract_id',
        'visa_type', 'stage', 'status',
        'sadad_reference', 'sadad_amount', 'sadad_paid_at',
        'entered_stage_at', 'stage_due_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'sadad_amount' => 'integer',
            'sadad_paid_at' => 'datetime',
            'entered_stage_at' => 'datetime',
            'stage_due_at' => 'datetime',
        ];
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VisaDocument::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(VisaStageEvent::class);
    }

    public function isSlaBreached(): bool
    {
        return $this->status === 'open'
            && $this->stage_due_at
            && $this->stage_due_at->isPast();
    }

    public function nextStage(): ?string
    {
        $i = array_search($this->stage, self::STAGES, true);

        return $i === false ? null : (self::STAGES[$i + 1] ?? null);
    }
}
