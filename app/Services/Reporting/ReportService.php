<?php

namespace App\Services\Reporting;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\VisaCase;
use App\Models\Worker;

/**
 * Builds tabular reports (§6.8). Returns column + row data that the controller
 * serves as JSON or streams as CSV (UTF-8 BOM for correct Arabic in Excel).
 * XLSX/PDF rendering is a documented seam (phpspreadsheet / mpdf).
 */
class ReportService
{
    public const REPORTS = ['workers', 'contracts', 'financial', 'visa_pipeline'];

    public function build(string $report): array
    {
        return match ($report) {
            'workers' => $this->workers(),
            'contracts' => $this->contracts(),
            'financial' => $this->financial(),
            'visa_pipeline' => $this->visaPipeline(),
            default => abort(404, 'Unknown report.'),
        };
    }

    protected function workers(): array
    {
        $rows = Worker::query()
            ->get(['id', 'name_en', 'name_ar', 'nationality', 'status', 'experience_years'])
            ->map(fn ($w) => $w->only(['id', 'name_en', 'name_ar', 'nationality', 'status', 'experience_years']))
            ->all();

        return [
            'columns' => ['id', 'name_en', 'name_ar', 'nationality', 'status', 'experience_years'],
            'rows' => $rows,
        ];
    }

    protected function contracts(): array
    {
        $rows = Contract::with('sponsor:id,name_en', 'worker:id,name_en')->get()
            ->map(fn ($c) => [
                'contract_no' => $c->contract_no,
                'sponsor' => $c->sponsor?->name_en,
                'worker' => $c->worker?->name_en,
                'status' => $c->status,
                'monthly_salary' => $c->monthly_salary,
                'warranty_ends_at' => $c->warranty_ends_at?->toDateString(),
                'warranty_void' => $c->warranty_void ? 'yes' : 'no',
            ])->all();

        return [
            'columns' => ['contract_no', 'sponsor', 'worker', 'status', 'monthly_salary', 'warranty_ends_at', 'warranty_void'],
            'rows' => $rows,
        ];
    }

    protected function financial(): array
    {
        $rows = Invoice::with('sponsor:id,name_en')->get()
            ->map(fn ($i) => [
                'invoice_no' => $i->invoice_no,
                'sponsor' => $i->sponsor?->name_en,
                'status' => $i->status,
                'total' => $i->total,
                'amount_paid' => $i->amount_paid,
                'balance' => $i->balance(),
            ])->all();

        return [
            'columns' => ['invoice_no', 'sponsor', 'status', 'total', 'amount_paid', 'balance'],
            'rows' => $rows,
        ];
    }

    protected function visaPipeline(): array
    {
        $rows = VisaCase::with('worker:id,name_en')->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'worker' => $v->worker?->name_en,
                'visa_type' => $v->visa_type,
                'stage' => $v->stage,
                'status' => $v->status,
                'sla_breached' => $v->isSlaBreached() ? 'yes' : 'no',
            ])->all();

        return [
            'columns' => ['id', 'worker', 'visa_type', 'stage', 'status', 'sla_breached'],
            'rows' => $rows,
        ];
    }
}
