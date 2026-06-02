<?php

namespace Tests\Feature;

use App\Models\Sponsor;
use App\Models\SponsorBlock;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Pii;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EligibilityCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_block_by_one_agency_is_visible_to_another(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $civilId = '291010112345';

        $agencyA = Tenant::create(['name_en' => 'A', 'name_ar' => 'أ', 'slug' => 'a', 'status' => 'active']);
        $agencyB = Tenant::create(['name_en' => 'B', 'name_ar' => 'ب', 'slug' => 'b', 'status' => 'active']);

        // Agency A needs an active subscription to pass the subscription gate.
        $plan = SubscriptionPlan::create([
            'key' => 'pro', 'name_en' => 'Pro', 'name_ar' => 'احترافي', 'yearly_price' => 0,
        ]);
        Subscription::create([
            'tenant_id' => $agencyA->id, 'plan_id' => $plan->id,
            'starts_at' => now(), 'ends_at' => now()->addYear(), 'status' => 'active',
        ]);

        // Agency B blocks the sponsor.
        SponsorBlock::create([
            'blocking_tenant_id' => $agencyB->id,
            'civil_id' => $civilId,
            'civil_id_hash' => Pii::hash($civilId),
            'reason_code' => 'non_payment',
            'status' => 'active',
        ]);

        // Agency A staff user checks eligibility.
        $userA = User::create([
            'tenant_id' => $agencyA->id, 'name' => 'Staff', 'email' => 's@a.test', 'password' => 'password123',
        ]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($agencyA->id);
        $userA->assignRole('agency-staff');

        $response = $this->actingAs($userA)
            ->withHeader('Accept', 'application/json')
            ->postJson('/api/v1/eligibility/check', ['civil_id' => $civilId]);

        $response->assertOk()
            ->assertJsonPath('status', 'blocked')
            ->assertJsonPath('blocked_by_agencies', 1)
            ->assertJsonPath('blocked_by_this_agency', false);
    }

    public function test_tenant_scope_isolates_sponsors(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $agencyA = Tenant::create(['name_en' => 'A', 'name_ar' => 'أ', 'slug' => 'a', 'status' => 'active']);
        $agencyB = Tenant::create(['name_en' => 'B', 'name_ar' => 'ب', 'slug' => 'b', 'status' => 'active']);

        $context = app(TenantContext::class);

        $context->set($agencyA);
        Sponsor::create(['civil_id' => '111', 'name_ar' => 'أ']);

        $context->set($agencyB);
        $this->assertSame(0, Sponsor::count(), 'Agency B must not see Agency A sponsors.');

        $context->set($agencyA);
        $this->assertSame(1, Sponsor::count());
    }
}
