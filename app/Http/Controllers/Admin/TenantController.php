<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;

/**
 * Vendor control plane: create / suspend / renew agency tenants and provision
 * their first admin user (agencies never self-register, §2).
 */
class TenantController extends Controller
{
    public function index()
    {
        return response()->json(Tenant::with('activeSubscription.plan')->latest()->paginate(20));
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'license_no' => ['nullable', 'string', 'max:64'],
            'contact_email' => ['required', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'allowed_identity_drivers' => ['nullable', 'array'],
            'plan_id' => ['required', Rule::exists('subscription_plans', 'id')],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', Rule::unique('users', 'email')],
            'admin_password' => ['required', 'string', 'min:10'],
        ]);

        $tenant = DB::transaction(function () use ($data, $audit) {
            $tenant = Tenant::create([
                'name_ar' => $data['name_ar'],
                'name_en' => $data['name_en'],
                'slug' => Str::slug($data['name_en']).'-'.Str::lower(Str::random(4)),
                'license_no' => $data['license_no'] ?? null,
                'status' => 'active',
                'default_locale' => 'ar',
                'contact_email' => $data['contact_email'],
                'contact_phone' => $data['contact_phone'] ?? null,
                'allowed_identity_drivers' => $data['allowed_identity_drivers'] ?? [config('identity.default')],
            ]);

            // First yearly subscription period.
            $plan = SubscriptionPlan::findOrFail($data['plan_id']);
            Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'starts_at' => now(),
                'ends_at' => now()->addYear(),
                'status' => 'active',
                'auto_renew' => true,
                'amount_paid' => $plan->yearly_price,
            ]);

            // Provision the first agency admin, scoped to this tenant.
            $admin = User::create([
                'tenant_id' => $tenant->id,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => $data['admin_password'],
                'locale' => 'ar',
            ]);

            // Assign the agency-admin role within this tenant's team context.
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
            $admin->assignRole('agency-admin');

            $audit->log('tenant.created', $tenant, ['admin_email' => $admin->email]);

            return $tenant;
        });

        return response()->json($tenant->load('activeSubscription.plan'), 201);
    }

    public function show(Tenant $tenant)
    {
        return response()->json($tenant->load('activeSubscription.plan', 'users'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $tenant->update($request->validate([
            'name_ar' => ['sometimes', 'string', 'max:255'],
            'name_en' => ['sometimes', 'string', 'max:255'],
            'contact_email' => ['sometimes', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'allowed_identity_drivers' => ['nullable', 'array'],
        ]));

        return response()->json($tenant);
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->update(['status' => 'archived']);
        $tenant->delete();

        return response()->noContent();
    }

    public function suspend(Tenant $tenant, AuditLogger $audit)
    {
        $tenant->update(['status' => 'suspended']);
        $audit->log('tenant.suspended', $tenant);

        return response()->json(['message' => 'Tenant suspended.', 'status' => $tenant->status]);
    }

    public function renew(Request $request, Tenant $tenant, AuditLogger $audit)
    {
        $data = $request->validate([
            'plan_id' => ['nullable', Rule::exists('subscription_plans', 'id')],
            'months' => ['nullable', 'integer', 'min:1', 'max:36'],
        ]);

        $current = $tenant->activeSubscription;
        $plan = isset($data['plan_id'])
            ? SubscriptionPlan::findOrFail($data['plan_id'])
            : $current?->plan ?? SubscriptionPlan::firstOrFail();

        // Extend from the later of now / current end (no lost time on renewal).
        $base = $current && $current->ends_at->isFuture() ? $current->ends_at->copy() : now();

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'starts_at' => $base,
            'ends_at' => $base->copy()->addMonths($data['months'] ?? 12),
            'status' => 'active',
            'auto_renew' => true,
            'amount_paid' => $plan->yearly_price,
        ]);

        $tenant->update(['status' => 'active']);
        $audit->log('tenant.renewed', $tenant, ['plan' => $plan->key, 'ends_at' => $subscription->ends_at->toDateString()]);

        return response()->json($subscription);
    }
}
