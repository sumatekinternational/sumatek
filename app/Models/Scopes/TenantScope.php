<?php

namespace App\Models\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope that constrains every tenant-owned model to the current tenant.
 *
 * Deny-by-default: if there is no tenant in context and scoping has not been
 * explicitly bypassed, the query returns nothing rather than leaking other
 * tenants' rows. (§2/§9)
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        if ($context->bypassed()) {
            return;
        }

        $column = $model->qualifyColumn(config('multitenancy.column', 'tenant_id'));

        if ($context->has()) {
            $builder->where($column, $context->id());

            return;
        }

        // No tenant resolved and not bypassed: expose nothing.
        $builder->whereRaw('1 = 0');
    }
}
