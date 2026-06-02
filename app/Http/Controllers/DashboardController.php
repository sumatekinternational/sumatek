<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\SponsorBlock;
use App\Models\VisaCase;
use App\Models\Worker;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * Agency operations dashboard (§6.8): workers, pipeline, revenue, blocked
 * sponsors and expiring documents at a glance.
 */
class DashboardController extends Controller
{
    public function __invoke(TenantContext $tenant)
    {
        $this->authorizeAbility('report.view');

        $workersByStatus = Worker::select('status', DB::raw('count(*) as c'))
            ->groupBy('status')->pluck('c', 'status');

        $pipelineByStage = VisaCase::where('status', 'open')
            ->select('stage', DB::raw('count(*) as c'))->groupBy('stage')->pluck('c', 'stage');

        return response()->json([
            'workers' => [
                'total' => Worker::count(),
                'by_status' => $workersByStatus,
            ],
            'contracts' => [
                'active' => Contract::where('status', 'active')->count(),
                'under_warranty_expiring_30d' => Contract::where('status', 'active')
                    ->where('warranty_void', false)
                    ->whereBetween('warranty_ends_at', [now(), now()->addDays(30)])
                    ->count(),
            ],
            'visa_pipeline' => [
                'open' => VisaCase::where('status', 'open')->count(),
                'sla_breached' => VisaCase::where('status', 'open')
                    ->whereNotNull('stage_due_at')->where('stage_due_at', '<', now())->count(),
                'by_stage' => $pipelineByStage,
            ],
            'billing' => [
                'outstanding_fils' => (int) Invoice::whereIn('status', ['issued', 'partially_paid', 'overdue'])
                    ->sum(DB::raw('total - amount_paid')),
                'overdue' => Invoice::where('status', 'overdue')->count(),
            ],
            'sponsors' => [
                // Blocks this agency has raised on the shared registry.
                'blocked_by_us' => $tenant->bypass(fn () => SponsorBlock::where('blocking_tenant_id', $tenant->id())
                    ->where('status', 'active')->count()),
            ],
            'documents_expiring_30d' => [
                'worker_passports' => Worker::whereBetween('passport_expiry', [now(), now()->addDays(30)])->count(),
            ],
        ]);
    }
}
