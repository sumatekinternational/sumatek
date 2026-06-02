<?php

namespace App\Services\Identity;

use App\Models\IdentityRead;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Pii;

/**
 * Orchestrates an identity read: enforces the agency's permitted drivers,
 * resolves the route, records a (non-PII) IdentityRead audit row, and returns
 * normalised data for auto-fill (§5/§9).
 */
class IdentityService
{
    public function __construct(
        protected IdentityReaderManager $manager,
        protected AuditLogger $audit,
    ) {}

    public function read(?string $driver, array $payload, User $user): IdentityData
    {
        $driver = $this->resolveDriver($driver, $user);

        $succeeded = false;
        $data = null;

        try {
            $data = $this->manager->driver($driver)->read($payload);
            $succeeded = true;

            return $data;
        } finally {
            IdentityRead::create([
                'user_id' => $user->id,
                'driver' => $driver,
                'document_type' => $data?->documentType ?? ($payload['document_type'] ?? null),
                'verification_level' => $data?->verificationLevel,
                'subject_hash' => Pii::hash($data?->number ?? ($payload['civil_id'] ?? null)),
                'succeeded' => $succeeded,
                'meta' => $data?->meta ?? [],
            ]);

            $this->audit->log('identity.read', null, [
                'driver' => $driver,
                'document_type' => $data?->documentType,
                'succeeded' => $succeeded,
            ]);
        }
    }

    /**
     * Pick a driver: explicit request wins, else the tenant's first allowed
     * driver, else the system default. The chosen driver must be permitted for
     * the agency.
     */
    protected function resolveDriver(?string $driver, User $user): string
    {
        $allowed = $user->tenant?->allowed_identity_drivers ?: [config('identity.default')];

        $driver ??= $allowed[0] ?? config('identity.default');

        if (! in_array($driver, $allowed, true) && ! in_array('*', $allowed, true)) {
            throw new IdentityReadException("Identity driver [$driver] is not enabled for this agency.");
        }

        return $driver;
    }
}
