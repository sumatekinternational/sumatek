<?php

namespace App\Services\Identity\Drivers;

use App\Services\Identity\IdentityData;
use App\Services\Identity\IdentityReaderInterface;
use App\Services\Identity\IdentityReadException;
use Illuminate\Support\Facades\Http;

/**
 * PACI Mobile ID "Hawyti" consent flow (§5.2). The agency shows a QR; the
 * sponsor approves in the Hawyti app; PACI returns verified data tied to that
 * consent session. This reader exchanges a completed session_id for data.
 */
class HawytiReader implements IdentityReaderInterface
{
    public function read(array $payload): IdentityData
    {
        $sessionId = $payload['session_id'] ?? null;

        if (! $sessionId) {
            throw new IdentityReadException('session_id is required for the Hawyti driver.');
        }

        $config = config('identity.drivers.hawyti');

        if (empty($config['base_url']) || empty($config['api_key'])) {
            throw new IdentityReadException('Hawyti driver is not configured (awaiting PACI approval).');
        }

        $response = Http::baseUrl($config['base_url'])
            ->withToken($config['api_key'])
            ->acceptJson()
            ->get('/consent-sessions/'.urlencode($sessionId));

        if ($response->failed()) {
            throw new IdentityReadException('Hawyti session lookup failed: HTTP '.$response->status());
        }

        $d = $response->json();

        if (($d['status'] ?? null) !== 'approved') {
            throw new IdentityReadException('Sponsor has not yet approved the Hawyti consent request.');
        }

        $c = $d['civilData'] ?? [];

        return new IdentityData(
            documentType: 'civil_id',
            number: $c['civilId'] ?? null,
            nameAr: $c['fullNameAr'] ?? null,
            nameEn: $c['fullNameEn'] ?? null,
            dateOfBirth: $c['dateOfBirth'] ?? null,
            sex: $c['sex'] ?? null,
            nationality: $c['nationality'] ?? null,
            addressAr: $c['addressAr'] ?? null,
            addressEn: $c['addressEn'] ?? null,
            verificationLevel: 'verified',
            meta: ['consent_id' => $sessionId],
        );
    }

    public function key(): string
    {
        return 'hawyti';
    }
}
