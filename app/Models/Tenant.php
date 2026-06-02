<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An agency tenant. Lives on the vendor control plane and is therefore NOT
 * tenant-scoped itself. (§2)
 */
class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name_en',
        'name_ar',
        'slug',
        'license_no',
        'status',          // onboarding | active | suspended | archived
        'default_locale',
        'contact_email',
        'contact_phone',
        'allowed_identity_drivers', // json: subset of identity drivers
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'allowed_identity_drivers' => 'array',
            'settings' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany('ends_at');
    }

    /**
     * Derive the lifecycle status from the latest subscription + grace window.
     * Returns: active | grace | suspended | none. (§2)
     */
    public function subscriptionStatus(): string
    {
        if ($this->status === 'suspended' || $this->status === 'archived') {
            return 'suspended';
        }

        $subscription = $this->activeSubscription;

        if (! $subscription) {
            return 'none';
        }

        return $subscription->lifecycleStatus();
    }
}
