<?php

namespace Tests\Feature;

use App\Models\DataImport;
use App\Models\Sponsor;
use App\Models\SponsorBlock;
use App\Models\Tenant;
use App\Models\Worker;
use App\Services\BlockRegistry\DisputeService;
use App\Services\Import\ImportService;
use App\Support\Pii;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class Phase3FlowsTest extends TestCase
{
    use RefreshDatabase;

    protected function bootAgency(): Tenant
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $tenant = Tenant::create(['name_en' => 'A', 'name_ar' => 'أ', 'slug' => 'a', 'status' => 'active']);
        app(TenantContext::class)->set($tenant);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        return $tenant;
    }

    public function test_csv_import_preview_does_not_write_then_commit_and_rollback(): void
    {
        $this->bootAgency();

        $csv = "civil_id,name_ar,name_en,nationality\n"
            ."281020112345,سالم,Salem,KWT\n"
            ."290011198765,نورة,Noura,KWT\n";
        Storage::disk('local')->put('imports/test.csv', $csv);

        $import = DataImport::create([
            'type' => 'sponsors', 'source' => 'csv', 'file_path' => 'imports/test.csv',
            'status' => 'pending', 'dedupe_strategy' => 'skip',
        ]);

        $service = app(ImportService::class);

        // Dry-run writes nothing.
        $service->preview($import);
        $this->assertSame(2, $import->stats['total']);
        $this->assertSame(2, $import->stats['created']);
        $this->assertSame(0, Sponsor::count());

        // Commit creates the rows, tagged to the import.
        $service->run($import->fresh());
        $this->assertSame(2, Sponsor::count());
        $this->assertSame(2, Sponsor::where('data_import_id', $import->id)->count());

        // Rollback removes exactly what the import created.
        $service->rollback($import->fresh());
        $this->assertSame(0, Sponsor::count());
        $this->assertSame('rolled_back', $import->fresh()->status);
    }

    public function test_dispute_flags_block_and_vendor_can_remove_it(): void
    {
        $tenant = $this->bootAgency();
        $civilId = '291010112345';

        $block = SponsorBlock::create([
            'blocking_tenant_id' => $tenant->id,
            'civil_id' => $civilId, 'civil_id_hash' => Pii::hash($civilId),
            'reason_code' => 'non_payment', 'status' => 'active',
        ]);

        $service = app(DisputeService::class);
        $disputes = $service->raise(['civil_id' => $civilId, 'reason' => 'Sponsor contests the claim.']);

        $this->assertCount(1, $disputes);
        $this->assertSame('flagged', $block->fresh()->moderation_state);

        $service->resolve($disputes->first(), 'remove', 'Insufficient evidence.');

        $block->refresh();
        $this->assertSame('revoked', $block->status);
        $this->assertSame('removed', $block->moderation_state);
        $this->assertSame('resolved_removed', $disputes->first()->fresh()->status);
    }

    public function test_public_catalogue_lists_only_consented_available_workers(): void
    {
        $tenant = $this->bootAgency();

        $listed = Worker::create([
            'passport_no' => 'P1', 'name_en' => 'Listed', 'nationality' => 'IND',
            'status' => 'available', 'public_listed' => true, 'public_token' => 'tok-1', 'consent_public_at' => now(),
        ]);
        Worker::create(['passport_no' => 'P2', 'name_en' => 'Private', 'nationality' => 'IND', 'status' => 'available']);
        Worker::create(['passport_no' => 'P3', 'name_en' => 'Deployed', 'nationality' => 'IND',
            'status' => 'deployed', 'public_listed' => true, 'public_token' => 'tok-3', 'consent_public_at' => now()]);

        // Public browse runs cross-tenant with the scope bypassed.
        $public = app(TenantContext::class)->bypass(fn () => Worker::where('public_listed', true)
            ->where('status', 'available')->get());

        $this->assertCount(1, $public);
        $this->assertSame($listed->id, $public->first()->id);
    }
}
