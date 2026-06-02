<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks or read-onlys tenants whose subscription has lapsed (§2).
 *
 *  active / grace  -> full access
 *  expired         -> read-only (GET/HEAD) or fully locked per policy
 */
class EnsureSubscriptionActive
{
    public function __construct(protected TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->context->current();

        // No tenant context here means a control-plane user; let it pass.
        if (! $tenant) {
            return $next($request);
        }

        $status = $tenant->subscriptionStatus();

        if (in_array($status, ['active', 'grace'], true)) {
            return $next($request);
        }

        // Expired (suspended/archived).
        $policy = config('subscription.expired_policy', 'read_only');
        $isRead = in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);

        if ($policy === 'read_only' && $isRead) {
            return $next($request);
        }

        return response()->json([
            'message' => __('subscription.expired'),
            'subscription_status' => $status,
            'renew_at' => route('tenants.renew', $tenant, false),
        ], 402); // Payment Required
    }
}
