<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the tenant for the request and loads it into the TenantContext (§2).
 *
 *  - Agency users (user.tenant_id set) are pinned to their own agency.
 *  - Vendor control-plane users (no tenant_id) operate on the control plane and
 *    may target a tenant via the X-Tenant header.
 *
 * The Spatie team id is set BEFORE any role/permission access so the user's
 * team-scoped roles resolve correctly (and aren't cached under a null team).
 */
class ResolveTenant
{
    public function __construct(protected TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user, 401);

        // Vendor control-plane users have no tenant of their own.
        if (! $user->tenant_id) {
            $this->scopeTo($user, $this->tenantFromHeader($request));

            return $next($request);
        }

        $tenant = $user->tenant;
        abort_unless($tenant, 403, 'Agency not found.');

        $this->scopeTo($user, $tenant);

        return $next($request);
    }

    protected function tenantFromHeader(Request $request): ?Tenant
    {
        $tenantId = $request->header(config('multitenancy.tenant_header', 'X-Tenant'));

        if (! $tenantId) {
            return null;
        }

        $tenant = Tenant::find($tenantId);
        abort_unless($tenant, 404, 'Tenant not found.');

        return $tenant;
    }

    protected function scopeTo($user, ?Tenant $tenant): void
    {
        if ($tenant) {
            $this->context->set($tenant);
        }

        // Scope role/permission checks to this team, then drop any role/perm
        // relations loaded earlier under a different (or null) team context.
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant?->id);
        $user->unsetRelation('roles')->unsetRelation('permissions');
    }
}
