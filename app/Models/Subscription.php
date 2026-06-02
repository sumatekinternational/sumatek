<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A yearly subscription period for a tenant (§2). The lifecycle is derived
 * from ends_at + the configured grace window.
 */
class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'starts_at',
        'ends_at',
        'status',        // active | cancelled
        'auto_renew',
        'amount_paid',   // KWD fils
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'auto_renew' => 'boolean',
            'amount_paid' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /**
     * active | grace | suspended (§2).
     */
    public function lifecycleStatus(): string
    {
        if ($this->status === 'cancelled') {
            return 'suspended';
        }

        $now = now();

        if ($this->ends_at->greaterThanOrEqualTo($now)) {
            return 'active';
        }

        $graceEnds = $this->ends_at->copy()->addDays(config('subscription.grace_days', 14));

        return $now->lessThanOrEqualTo($graceEnds) ? 'grace' : 'suspended';
    }
}
