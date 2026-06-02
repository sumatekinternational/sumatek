<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionPlanController extends Controller
{
    public function index()
    {
        return response()->json(SubscriptionPlan::all());
    }

    public function store(Request $request)
    {
        return response()->json(SubscriptionPlan::create($this->validatePlan($request)), 201);
    }

    public function show(SubscriptionPlan $plan)
    {
        return response()->json($plan);
    }

    public function update(Request $request, SubscriptionPlan $plan)
    {
        $plan->update($this->validatePlan($request, $plan));

        return response()->json($plan);
    }

    public function destroy(SubscriptionPlan $plan)
    {
        $plan->update(['is_active' => false]);

        return response()->noContent();
    }

    protected function validatePlan(Request $request, ?SubscriptionPlan $plan = null): array
    {
        return $request->validate([
            'key' => [$plan ? 'sometimes' : 'required', 'string', Rule::in(['basic', 'pro', 'enterprise'])],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'yearly_price' => ['required', 'integer', 'min:0'], // KWD fils
            'currency' => ['nullable', 'string', 'size:3'],
            'features' => ['nullable', 'array'],
            'limits' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
