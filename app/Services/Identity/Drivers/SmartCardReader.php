<?php

namespace App\Services\Identity\Drivers;

use App\Services\Identity\IdentityData;
use App\Services\Identity\IdentityReaderInterface;
use App\Services\Identity\IdentityReadException;

/**
 * Smart Civil ID card reader route (§5.3). A standards-compliant reader at the
 * agency desk pulls the data group off the chip client-side; the desk app
 * posts the parsed fields here for validation and normalisation. Requires PACI
 * approval + reader hardware.
 */
class SmartCardReader implements IdentityReaderInterface
{
    public function read(array $payload): IdentityData
    {
        $card = $payload['card_data'] ?? null;

        if (! is_array($card) || empty($card['civil_id'])) {
            throw new IdentityReadException('Parsed card_data with at least a civil_id is required.');
        }

        return new IdentityData(
            documentType: 'civil_id',
            number: $card['civil_id'],
            nameAr: $card['name_ar'] ?? null,
            nameEn: $card['name_en'] ?? null,
            dateOfBirth: $card['date_of_birth'] ?? null,
            sex: $card['sex'] ?? null,
            nationality: $card['nationality'] ?? null,
            addressAr: $card['address_ar'] ?? null,
            addressEn: $card['address_en'] ?? null,
            expiry: $card['card_expiry'] ?? null,
            verificationLevel: 'verified',
            meta: ['blood_type' => $card['blood_type'] ?? null, 'reader' => $payload['reader'] ?? null],
        );
    }

    public function key(): string
    {
        return 'smartcard';
    }
}
