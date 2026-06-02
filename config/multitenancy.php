<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenancy model (§2)
    |--------------------------------------------------------------------------
    |
    | Logical multi-tenancy: a single shared database where every business
    | table carries a `tenant_id` and is automatically scoped by the
    | App\Models\Scopes\TenantScope global scope (applied via the
    | App\Models\Concerns\BelongsToTenant trait).
    |
    */

    'column' => 'tenant_id',

    // Roles that operate on the vendor control plane and are therefore NOT
    // constrained to a single tenant. These users may resolve any tenant
    // context explicitly via the X-Tenant header.
    'control_plane_roles' => [
        'super-admin',
        'vendor-support',
        'vendor-billing',
    ],

    // Header used by control-plane users to act within a specific tenant.
    'tenant_header' => 'X-Tenant',

];
