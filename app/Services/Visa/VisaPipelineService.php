<?php

namespace App\Services\Visa;

use App\Models\VisaCase;
use App\Models\VisaDocument;
use App\Models\VisaStageEvent;
use App\Services\Audit\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Drives the Visa 20 deployment pipeline (§6.4): opens cases with their
 * per-stage document checklist, enforces stage gating, computes SLAs and logs
 * every transition.
 */
class VisaPipelineService
{
    /** Standard per-stage document checklist (key => [en, ar, required]). */
    public const CHECKLIST = [
        'documents' => [
            'salary_certificate' => ['Salary Certificate', 'شهادة راتب', true],
            'rental_agreement' => ['Rental Agreement', 'عقد إيجار', true],
            'civil_id_copy' => ['Sponsor Civil ID Copy', 'صورة البطاقة المدنية للكفيل', true],
        ],
        'medical' => [
            'medical_report' => ['Medical Fitness Report', 'تقرير اللياقة الطبية', true],
        ],
        'visa_issued' => [
            'visa_copy' => ['Issued Visa Copy', 'صورة التأشيرة الصادرة', true],
        ],
        'travel' => [
            'ticket' => ['Travel Ticket', 'تذكرة السفر', true],
        ],
    ];

    public function __construct(
        protected TenantContext $tenant,
        protected AuditLogger $audit,
    ) {}

    public function open(array $data): VisaCase
    {
        return DB::transaction(function () use ($data) {
            $case = VisaCase::create(array_merge($data, [
                'stage' => 'intake',
                'status' => 'open',
                'entered_stage_at' => now(),
                'stage_due_at' => $this->dueFor('intake'),
            ]));

            $this->seedChecklist($case);
            $this->logTransition($case, null, 'intake');

            $this->audit->log('visa.case_opened', $case);

            return $case;
        });
    }

    /**
     * Advance to the next stage. Required documents for the CURRENT stage must
     * be verified before moving on.
     */
    public function advance(VisaCase $case, ?string $note = null): VisaCase
    {
        abort_if($case->status !== 'open', 422, 'Case is not open.');

        $next = $case->nextStage();

        if ($next === null) {
            throw ValidationException::withMessages(['stage' => 'Case is already at the final stage.']);
        }

        $this->assertStageDocumentsVerified($case);

        return DB::transaction(function () use ($case, $next, $note) {
            $from = $case->stage;
            $case->stage = $next;
            $case->entered_stage_at = now();
            $case->stage_due_at = $this->dueFor($next);

            if ($next === 'deployed') {
                $case->status = 'completed';
                $case->worker?->update(['status' => 'deployed']);
            }

            $case->save();
            $this->logTransition($case, $from, $next, $note);
            $this->audit->log('visa.stage_advanced', $case, ['from' => $from, 'to' => $next]);

            return $case;
        });
    }

    public function recordSadad(VisaCase $case, string $reference, int $amountFils): VisaCase
    {
        $case->update([
            'sadad_reference' => $reference,
            'sadad_amount' => $amountFils,
            'sadad_paid_at' => now(),
        ]);

        $this->audit->log('visa.sadad_recorded', $case, ['reference' => $reference, 'amount' => $amountFils]);

        return $case;
    }

    protected function assertStageDocumentsVerified(VisaCase $case): void
    {
        $missing = $case->documents()
            ->where('stage', $case->stage)
            ->where('required', true)
            ->where('status', '!=', 'verified')
            ->pluck('key');

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'documents' => 'Required documents not verified for stage '
                    ."{$case->stage}: ".$missing->implode(', '),
            ]);
        }
    }

    protected function seedChecklist(VisaCase $case): void
    {
        foreach (self::CHECKLIST as $stage => $docs) {
            foreach ($docs as $key => [$en, $ar, $required]) {
                VisaDocument::create([
                    'visa_case_id' => $case->id,
                    'stage' => $stage,
                    'key' => $key,
                    'label_en' => $en,
                    'label_ar' => $ar,
                    'required' => $required,
                    'status' => 'pending',
                ]);
            }
        }
    }

    protected function dueFor(string $stage): Carbon
    {
        return now()->addDays(VisaCase::STAGE_SLA_DAYS[$stage] ?? 7);
    }

    protected function logTransition(VisaCase $case, ?string $from, string $to, ?string $note = null): void
    {
        VisaStageEvent::create([
            'visa_case_id' => $case->id,
            'from_stage' => $from,
            'to_stage' => $to,
            'actor_user_id' => Auth::id(),
            'note' => $note,
        ]);
    }
}
