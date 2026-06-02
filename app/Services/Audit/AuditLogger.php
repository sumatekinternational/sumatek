<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Writes immutable audit records for sensitive actions and mirrors them to a
 * dedicated, long-retention log channel (§9).
 */
class AuditLogger
{
    public function __construct(protected TenantContext $tenant) {}

    public function log(string $action, ?Model $auditable = null, array $metadata = []): AuditLog
    {
        $entry = AuditLog::create([
            'tenant_id' => $this->tenant->id(),
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'metadata' => $metadata,
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 500),
        ]);

        Log::channel('audit')->info($action, [
            'audit_id' => $entry->id,
            'tenant_id' => $entry->tenant_id,
            'user_id' => $entry->user_id,
            'auditable' => $auditable ? $auditable::class.'#'.$auditable->getKey() : null,
            'metadata' => $metadata,
        ]);

        return $entry;
    }
}
