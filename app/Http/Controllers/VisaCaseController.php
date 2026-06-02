<?php

namespace App\Http\Controllers;

use App\Models\VisaCase;
use App\Services\Visa\VisaPipelineService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VisaCaseController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAbility('visa.view');

        $cases = VisaCase::query()
            ->with(['worker:id,name_en,nationality', 'sponsor:id,name_ar,name_en'])
            ->when($request->string('stage')->toString(), fn ($q, $s) => $q->where('stage', $s))
            ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json($cases);
    }

    /** Kanban board: cases grouped by stage, with SLA-breach flags. */
    public function board()
    {
        $this->authorizeAbility('visa.view');

        $cases = VisaCase::where('status', 'open')->get();

        $board = collect(VisaCase::STAGES)->mapWithKeys(fn ($stage) => [$stage => []])->toArray();

        foreach ($cases as $case) {
            $board[$case->stage][] = [
                'id' => $case->id,
                'worker_id' => $case->worker_id,
                'sponsor_id' => $case->sponsor_id,
                'stage_due_at' => $case->stage_due_at?->toIso8601String(),
                'sla_breached' => $case->isSlaBreached(),
            ];
        }

        return response()->json(['stages' => VisaCase::STAGES, 'board' => $board]);
    }

    public function store(Request $request, VisaPipelineService $service)
    {
        $this->authorizeAbility('visa.manage');

        $data = $request->validate([
            'worker_id' => ['required', Rule::exists('workers', 'id')],
            'sponsor_id' => ['nullable', Rule::exists('sponsors', 'id')],
            'contract_id' => ['nullable', Rule::exists('contracts', 'id')],
            'visa_type' => ['nullable', 'string'],
        ]);

        return response()->json($service->open($data)->load('documents'), 201);
    }

    public function show(VisaCase $visaCase)
    {
        $this->authorizeAbility('visa.view');

        return response()->json($visaCase->load('documents', 'events', 'worker', 'sponsor'));
    }

    public function advance(Request $request, VisaCase $visaCase, VisaPipelineService $service)
    {
        $this->authorizeAbility('visa.manage');

        $data = $request->validate(['note' => ['nullable', 'string']]);

        return response()->json($service->advance($visaCase, $data['note'] ?? null));
    }

    public function sadad(Request $request, VisaCase $visaCase, VisaPipelineService $service)
    {
        $this->authorizeAbility('visa.manage');

        $data = $request->validate([
            'reference' => ['required', 'string'],
            'amount' => ['required', 'integer', 'min:0'],
        ]);

        return response()->json($service->recordSadad($visaCase, $data['reference'], $data['amount']));
    }
}
