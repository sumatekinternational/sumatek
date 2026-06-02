<?php

namespace App\Http\Controllers;

use App\Services\Reporting\ReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Exportable reports with Arabic support (§6.8). */
class ReportController extends Controller
{
    public function show(string $report, ReportService $service)
    {
        $this->authorizeAbility('report.view');
        $this->assertReport($report);

        return response()->json(array_merge(
            $service->build($report),
            ['report' => $report, 'generated_at' => now()->toIso8601String()],
        ));
    }

    public function export(Request $request, string $report, ReportService $service): StreamedResponse
    {
        $this->authorizeAbility('report.view');
        $this->assertReport($report);

        $data = $service->build($report);
        $filename = "{$report}-".now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel renders Arabic correctly.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $data['columns']);
            foreach ($data['rows'] as $row) {
                fputcsv($out, array_map(fn ($c) => $row[$c] ?? '', $data['columns']));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function assertReport(string $report): void
    {
        abort_unless(in_array($report, ReportService::REPORTS, true), 404, 'Unknown report.');
    }
}
