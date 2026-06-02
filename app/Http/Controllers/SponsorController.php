<?php

namespace App\Http\Controllers;

use App\Models\Sponsor;
use App\Support\Pii;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SponsorController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAbility('sponsor.view');

        $sponsors = Sponsor::query()
            ->when($request->string('q')->toString(), function ($query, $q) {
                $query->where(fn ($w) => $w
                    ->where('name_ar', 'like', "%$q%")
                    ->orWhere('name_en', 'like', "%$q%")
                    ->orWhere('phone', 'like', "%$q%"));
            })
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json($sponsors);
    }

    public function store(Request $request)
    {
        $this->authorizeAbility('sponsor.manage');

        $data = $this->validateSponsor($request);

        // Dedupe within the tenant on Civil ID hash (§6.6).
        $existing = Sponsor::where('civil_id_hash', Pii::hash($data['civil_id']))->first();
        abort_if($existing, 409, __('sponsor.duplicate'));

        $sponsor = Sponsor::create($data);

        return response()->json($sponsor, 201);
    }

    public function show(Sponsor $sponsor)
    {
        $this->authorizeAbility('sponsor.view');

        return response()->json($sponsor);
    }

    public function update(Request $request, Sponsor $sponsor)
    {
        $this->authorizeAbility('sponsor.manage');

        $sponsor->update($this->validateSponsor($request, $sponsor));

        return response()->json($sponsor);
    }

    public function destroy(Sponsor $sponsor)
    {
        $this->authorizeAbility('sponsor.manage');

        $sponsor->delete();

        return response()->noContent();
    }

    protected function validateSponsor(Request $request, ?Sponsor $sponsor = null): array
    {
        return $request->validate([
            'civil_id' => [$sponsor ? 'sometimes' : 'required', 'string', 'min:6', 'max:32'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'sex' => ['nullable', Rule::in(['M', 'F'])],
            'nationality' => ['nullable', 'string', 'max:3'],
            'address_ar' => ['nullable', 'string', 'max:1000'],
            'address_en' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email'],
            'verification_level' => ['nullable', Rule::in(['verified', 'best_effort', 'unverified'])],
            'identity_source' => ['nullable', 'string', 'max:32'],
            'consent_captured_at' => ['nullable', 'date'],
        ]);
    }

    protected function authorizeAbility(string $ability): void
    {
        abort_unless(request()->user()?->can($ability), 403);
    }
}
