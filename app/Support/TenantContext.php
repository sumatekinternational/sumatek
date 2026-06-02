<?php

namespace App\Support;

use App\Models\Tenant;
use Closure;

/**
 * Holds the tenant resolved for the current request / job. Bound as a
 * singleton so the TenantScope and BelongsToTenant trait can read it without
 * threading it through every call. (§2)
 */
class TenantContext
{
    protected ?Tenant $tenant = null;

    protected bool $bypassed = false;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function forget(): void
    {
        $this->tenant = null;
    }

    public function current(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function has(): bool
    {
        return $this->tenant !== null;
    }

    public function bypassed(): bool
    {
        return $this->bypassed;
    }

    /**
     * Run a callback with tenant scoping disabled. Used by seeders, the vendor
     * control plane, and cross-tenant features such as the block registry.
     */
    public function bypass(Closure $callback): mixed
    {
        $previous = $this->bypassed;
        $this->bypassed = true;

        try {
            return $callback();
        } finally {
            $this->bypassed = $previous;
        }
    }
}
