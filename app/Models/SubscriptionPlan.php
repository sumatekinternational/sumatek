<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Vendor-defined plan tier (Basic / Pro / Enterprise). Pricing is a business
 * input (§13); feature flags default from config('subscription.tiers'). (§2)
 */
class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',            // basic | pro | enterprise
        'name_en',
        'name_ar',
        'yearly_price',   // in KWD fils (integer) to avoid float drift
        'currency',
        'features',       // json feature flags
        'limits',         // json limits (users, workers)
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'limits' => 'array',
            'is_active' => 'boolean',
            'yearly_price' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function hasFeature(string $feature): bool
    {
        $features = $this->features ?? [];

        return in_array('*', $features, true) || in_array($feature, $features, true);
    }
}
