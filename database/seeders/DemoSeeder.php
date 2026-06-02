<?php

namespace Database\Seeders;

use App\Models\Sponsor;
use App\Models\SponsorBlock;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Worker;
use App\Support\Pii;
use App\Support\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Demo dataset so the flagship flow is exercisable out of the box:
 * Agency B blocks a Civil ID; an eligibility check from Agency A returns
 * "blocked". Do not run in production.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Vendor super-admin (global role, no tenant).
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@vendor.test'],
            ['name' => 'Vendor Super Admin', 'password' => 'password123', 'locale' => 'en']
        );
        $superAdmin->assignRole('super-admin');

        $pro = SubscriptionPlan::where('key', 'pro')->firstOrFail();

        $agencyA = $this->makeAgency('Al Salam Manpower', 'مكتب السلام للعمالة', 'a.admin@agency.test', $pro);
        $agencyB = $this->makeAgency('Gulf Domestic Labour', 'الخليج للعمالة المنزلية', 'b.admin@agency.test', $pro);

        $sharedCivilId = '291010112345'; // the sponsor both agencies will see

        // Agency A records the sponsor.
        $this->asTenant($agencyA, function () use ($sharedCivilId) {
            Sponsor::create([
                'civil_id' => $sharedCivilId,
                'name_ar' => 'محمد الأحمد',
                'name_en' => 'Mohammed Al-Ahmad',
                'nationality' => 'KWT',
                'phone' => '+96550000000',
                'verification_level' => 'verified',
                'identity_source' => 'fake',
                'consent_captured_at' => now(),
            ]);

            Worker::create([
                'passport_no' => 'P1234567',
                'name_en' => 'Asha Kumari',
                'nationality' => 'IND',
                'status' => 'available',
                'skills' => ['cooking', 'childcare'],
                'languages' => ['en', 'hi'],
                'experience_years' => 4,
            ]);
        });

        // Agency B blocks that same sponsor — this is what Agency A will detect.
        $this->asTenant($agencyB, function () use ($agencyB, $sharedCivilId) {
            SponsorBlock::create([
                'blocking_tenant_id' => $agencyB->id,
                'civil_id' => $sharedCivilId,
                'civil_id_hash' => Pii::hash($sharedCivilId),
                'sponsor_name_en' => 'Mohammed Al-Ahmad',
                'reason_code' => 'non_payment',
                'note' => 'Did not settle agency fees for previous placement.',
                'status' => 'active',
                'review_at' => now()->addYear(),
                'created_by_user_id' => $agencyB->users()->first()->id,
                'consent_captured_at' => now(),
            ]);
        });
    }

    protected function makeAgency(string $en, string $ar, string $adminEmail, SubscriptionPlan $plan): Tenant
    {
        $tenant = Tenant::updateOrCreate(
            ['slug' => Str::slug($en)],
            [
                'name_en' => $en,
                'name_ar' => $ar,
                'status' => 'active',
                'default_locale' => 'ar',
                'contact_email' => $adminEmail,
                'allowed_identity_drivers' => ['fake', 'mrz_ocr'],
            ]
        );

        Subscription::firstOrCreate(
            ['tenant_id' => $tenant->id, 'plan_id' => $plan->id, 'status' => 'active'],
            ['starts_at' => now(), 'ends_at' => now()->addYear(), 'amount_paid' => $plan->yearly_price]
        );

        $admin = User::updateOrCreate(
            ['email' => $adminEmail],
            ['tenant_id' => $tenant->id, 'name' => "$en Admin", 'password' => 'password123', 'locale' => 'ar']
        );

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $admin->assignRole('agency-admin');

        return $tenant;
    }

    protected function asTenant(Tenant $tenant, \Closure $callback): void
    {
        $context = app(TenantContext::class);
        $context->set($tenant);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        try {
            $callback();
        } finally {
            $context->forget();
        }
    }
}
