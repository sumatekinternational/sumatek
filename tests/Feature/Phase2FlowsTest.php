<?php

namespace Tests\Feature;

use App\Models\Sponsor;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Worker;
use App\Services\Contracts\ContractService;
use App\Services\Contracts\TransferService;
use App\Services\Visa\VisaPipelineService;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class Phase2FlowsTest extends TestCase
{
    use RefreshDatabase;

    protected function bootAgency(): Tenant
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $tenant = Tenant::create(['name_en' => 'A', 'name_ar' => 'أ', 'slug' => 'a', 'status' => 'active']);
        $plan = SubscriptionPlan::create(['key' => 'pro', 'name_en' => 'Pro', 'name_ar' => 'برو', 'yearly_price' => 0]);
        Subscription::create([
            'tenant_id' => $tenant->id, 'plan_id' => $plan->id,
            'starts_at' => now(), 'ends_at' => now()->addYear(), 'status' => 'active',
        ]);

        app(TenantContext::class)->set($tenant);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        return $tenant;
    }

    public function test_contract_activation_sets_six_month_warranty(): void
    {
        $this->bootAgency();

        $sponsor = Sponsor::create(['civil_id' => '123', 'name_ar' => 'كفيل']);
        $worker = Worker::create(['passport_no' => 'P1', 'name_en' => 'W', 'nationality' => 'IND']);

        $service = app(ContractService::class);
        $contract = $service->create([
            'sponsor_id' => $sponsor->id, 'worker_id' => $worker->id,
            'monthly_salary' => 120_000, 'duration_months' => 24, 'warranty_months' => 6,
        ]);

        $start = now()->startOfDay();
        $service->activate($contract, $start);

        $this->assertSame('active', $contract->status);
        $this->assertEquals($start->copy()->addMonths(6)->toDateString(), $contract->warranty_ends_at->toDateString());
        $this->assertTrue($contract->isUnderWarranty());
    }

    public function test_unauthorized_transfer_voids_warranty(): void
    {
        $this->bootAgency();

        $from = Sponsor::create(['civil_id' => 'A1', 'name_ar' => 'من']);
        $to = Sponsor::create(['civil_id' => 'B1', 'name_ar' => 'إلى']);
        $worker = Worker::create(['passport_no' => 'P2', 'name_en' => 'W2', 'nationality' => 'PHL']);

        $contracts = app(ContractService::class);
        $contract = $contracts->create([
            'sponsor_id' => $from->id, 'worker_id' => $worker->id, 'monthly_salary' => 100_000,
        ]);
        $contracts->activate($contract, now());
        $this->assertTrue($contract->isUnderWarranty());

        $transfers = app(TransferService::class);
        $transfer = $transfers->request($contract, ['to_sponsor_id' => $to->id, 'authorized' => false]);
        $transfers->advance($transfer, 'completed');

        $contract->refresh();
        $this->assertTrue($contract->warranty_void);
        $this->assertSame('transferred', $contract->status);
        $this->assertFalse($contract->isUnderWarranty());
    }

    public function test_visa_pipeline_blocks_advance_until_documents_verified(): void
    {
        $this->bootAgency();

        $worker = Worker::create(['passport_no' => 'P3', 'name_en' => 'W3', 'nationality' => 'IND']);

        $service = app(VisaPipelineService::class);
        $case = $service->open(['worker_id' => $worker->id]);

        $this->assertSame('intake', $case->stage);

        // intake -> documents (intake has no required docs) is allowed.
        $service->advance($case);
        $this->assertSame('documents', $case->fresh()->stage);

        // documents stage has required docs that are still pending -> blocked.
        $this->expectException(ValidationException::class);
        $service->advance($case->fresh());
    }
}
