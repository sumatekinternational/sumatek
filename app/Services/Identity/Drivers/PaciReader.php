<?php

namespace App\Services\Identity\Drivers;

use App\Services\Identity\IdentityData;
use App\Services\Identity\IdentityReaderInterface;
use App\Services\Identity\IdentityReadException;
use Illuminate\Support\Facades\Http;

/**
 * PACI (Public Authority for Civil Information) API route (§5.1) — the
 * preferred, verified source. Requires a government data-sharing contract;
 * credentials live in config('identity.drivers.paci').
 *
 * The exact PACI response schema is finalised on approval; the mapping below
 * is the integration seam to adjust once the contract lands.
 */
class PaciReader implements IdentityReaderInterface
{
    public function read(array $payload): IdentityData
    {
        $civilId = $payload['civil_id'] ?? null;

        if (! $civilId) {
            throw new IdentityReadException('civil_id is required for the PACI driver.');
        }

        $config = config('identity.drivers.paci');

        if (empty($config['base_url']) || empty($config['api_key'])) {
            throw new IdentityReadException('PACI driver is not configured (awaiting data-sharing approval).');
        }

        $response = Http::baseUrl($config['base_url'])
            ->withToken($config['api_key'])
            ->timeout($config['timeout'] ?? 10)
            ->acceptJson()
            ->get('/civil-records/'.urlencode($civilId));

        if ($response->failed()) {
            throw new IdentityReadException('PACI lookup failed: HTTP '.$response->status());
        }

        $d = $response->json();

        return new IdentityData(
            documentType: 'civil_id',
            number: $civilId,
            nameAr: $d['fullNameAr'] ?? null,
            nameEn: $d['fullNameEn'] ?? null,
            dateOfBirth: $d['dateOfBirth'] ?? null,
            sex: $d['sex'] ?? null,
            nationality: $d['nationality'] ?? null,
            addressAr: $d['addressAr'] ?? null,
            addressEn: $d['addressEn'] ?? null,
            expiry: $d['cardExpiry'] ?? null,
            verificationLevel: 'verified',
            meta: ['card_status' => $d['cardStatus'] ?? null],
        );
    }

    public function key(): string
    {
        return 'paci';
    }
}
