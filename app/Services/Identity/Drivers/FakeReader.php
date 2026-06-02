<?php

namespace App\Services\Identity\Drivers;

use App\Services\Identity\IdentityData;
use App\Services\Identity\IdentityReaderInterface;
use App\Services\Identity\IdentityReadException;
use App\Services\Identity\MrzParser;

/**
 * Development / demo reader (§5). Returns deterministic data so the full
 * scan -> auto-fill -> eligibility flow works before any government approval or
 * hardware is in place. If an MRZ is supplied it is parsed for real.
 */
class FakeReader implements IdentityReaderInterface
{
    public function __construct(protected MrzParser $parser) {}

    public function read(array $payload): IdentityData
    {
        if (! empty($payload['mrz'])) {
            $data = $this->parser->parse($payload['mrz']);
            $data->verificationLevel = 'unverified';

            return $data;
        }

        $civilId = $payload['civil_id'] ?? null;

        if (! $civilId) {
            throw new IdentityReadException('Provide civil_id or mrz for the fake driver.');
        }

        return new IdentityData(
            documentType: 'civil_id',
            number: $civilId,
            nameAr: 'مستخدم تجريبي',
            nameEn: 'Demo Sponsor',
            dateOfBirth: '1985-04-12',
            sex: 'M',
            nationality: 'KWT',
            addressAr: 'حولي، الكويت',
            addressEn: 'Hawalli, Kuwait',
            verificationLevel: 'unverified',
            meta: ['fake' => true],
        );
    }

    public function key(): string
    {
        return 'fake';
    }
}
