<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

/**
 * Seeds default plan tiers from config (§2). Prices are placeholders in KWD
 * fils (1 KWD = 1000 fils) pending the business pricing decision (§13).
 */
class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = config('subscription.tiers');

        $defaults = [
            'basic' => ['name_en' => 'Basic', 'name_ar' => 'أساسي', 'yearly_price' => 300_000],
            'pro' => ['name_en' => 'Pro', 'name_ar' => 'احترافي', 'yearly_price' => 750_000],
            'enterprise' => ['name_en' => 'Enterprise', 'name_ar' => 'مؤسسي', 'yearly_price' => 1_800_000],
        ];

        foreach ($defaults as $key => $meta) {
            SubscriptionPlan::updateOrCreate(['key' => $key], [
                'name_en' => $meta['name_en'],
                'name_ar' => $meta['name_ar'],
                'yearly_price' => $meta['yearly_price'],
                'currency' => 'KWD',
                'features' => $tiers[$key]['features'] ?? [],
                'limits' => $tiers[$key]['limits'] ?? [],
                'is_active' => true,
            ]);
        }
    }
}
