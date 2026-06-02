<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use App\Support\TenantContext;
use Illuminate\Http\Request;

/**
 * Public, unauthenticated worker catalogue (§6.2). Exposes only consented,
 * available workers and only non-PII fields. Runs cross-tenant (bypasses the
 * tenant scope) since there is no authenticated agency context.
 */
class PublicCatalogueController extends Controller
{
    public function index(Request $request, TenantContext $tenant)
    {
        return $tenant->bypass(function () use ($request) {
            $workers = Worker::query()
                ->where('public_listed', true)
                ->where('status', 'available')
                ->when($request->string('nationality')->toString(), fn ($q, $n) => $q->where('nationality', $n))
                ->when($request->string('skill')->toString(), fn ($q, $s) => $q->whereJsonContains('skills', $s))
                ->orderByDesc('updated_at')
                ->paginate($request->integer('per_page', 20), Worker::PUBLIC_FIELDS);

            return response()->json($workers);
        });
    }

    public function show(TenantContext $tenant, string $token)
    {
        return $tenant->bypass(function () use ($token) {
            $worker = Worker::where('public_token', $token)
                ->where('public_listed', true)
                ->first(Worker::PUBLIC_FIELDS);

            abort_unless($worker, 404);

            return response()->json($worker);
        });
    }
}
