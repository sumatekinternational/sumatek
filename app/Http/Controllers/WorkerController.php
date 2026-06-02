<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WorkerController extends Controller
{
    /** Consent + list a worker in the public catalogue (§6.2). */
    public function publish(Request $request, Worker $worker)
    {
        $this->authorizeAbility('worker.manage');

        $request->validate(['consent' => ['accepted']]);

        $worker->update([
            'public_listed' => true,
            'public_token' => $worker->public_token ?? (string) Str::uuid(),
            'consent_public_at' => now(),
        ]);

        return response()->json($worker);
    }

    public function unpublish(Worker $worker)
    {
        $this->authorizeAbility('worker.manage');

        $worker->update(['public_listed' => false, 'consent_public_at' => null]);

        return response()->json($worker);
    }

    public function index(Request $request)
    {
        $this->authorizeAbility('worker.view');

        $workers = Worker::query()
            ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
            ->when($request->string('nationality')->toString(), fn ($q, $n) => $q->where('nationality', $n))
            ->when($request->string('q')->toString(), fn ($q, $t) => $q
                ->where(fn ($w) => $w->where('name_ar', 'like', "%$t%")->orWhere('name_en', 'like', "%$t%")))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json($workers);
    }

    public function store(Request $request)
    {
        $this->authorizeAbility('worker.manage');

        $worker = Worker::create($this->validateWorker($request));

        return response()->json($worker, 201);
    }

    public function show(Worker $worker)
    {
        $this->authorizeAbility('worker.view');

        return response()->json($worker);
    }

    public function update(Request $request, Worker $worker)
    {
        $this->authorizeAbility('worker.manage');

        $worker->update($this->validateWorker($request, $worker));

        return response()->json($worker);
    }

    public function destroy(Worker $worker)
    {
        $this->authorizeAbility('worker.manage');

        $worker->delete();

        return response()->noContent();
    }

    protected function validateWorker(Request $request, ?Worker $worker = null): array
    {
        return $request->validate([
            'passport_no' => [$worker ? 'sometimes' : 'required', 'string', 'max:32'],
            'passport_expiry' => ['nullable', 'date'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'nationality' => ['required', 'string', 'max:3'],
            'date_of_birth' => ['nullable', 'date'],
            'sex' => ['nullable', Rule::in(['M', 'F'])],
            'skills' => ['nullable', 'array'],
            'languages' => ['nullable', 'array'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'medical_status' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', Rule::in(Worker::STATUSES)],
            'source_agency' => ['nullable', 'string', 'max:255'],
            'verification_level' => ['nullable', Rule::in(['verified', 'best_effort', 'unverified'])],
            'identity_source' => ['nullable', 'string', 'max:32'],
        ]);
    }

    protected function authorizeAbility(string $ability): void
    {
        abort_unless(request()->user()?->can($ability), 403);
    }
}
