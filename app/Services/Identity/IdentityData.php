<?php

namespace App\Services\Identity;

/**
 * Normalised result of an identity read, regardless of capture route (§5).
 * All readers return this shape so callers and auto-fill are driver-agnostic.
 */
class IdentityData
{
    public function __construct(
        public string $documentType,        // civil_id | passport
        public ?string $number = null,      // civil id or passport number
        public ?string $nameAr = null,
        public ?string $nameEn = null,
        public ?string $dateOfBirth = null, // Y-m-d
        public ?string $sex = null,         // M | F
        public ?string $nationality = null, // ISO-3166 alpha-3 where possible
        public ?string $addressAr = null,
        public ?string $addressEn = null,
        public ?string $expiry = null,      // Y-m-d (passport)
        public string $verificationLevel = 'unverified', // verified | best_effort | unverified
        public array $meta = [],            // non-PII diagnostics (checksums, card validity)
    ) {}

    public function toArray(): array
    {
        return [
            'document_type' => $this->documentType,
            'number' => $this->number,
            'name_ar' => $this->nameAr,
            'name_en' => $this->nameEn,
            'date_of_birth' => $this->dateOfBirth,
            'sex' => $this->sex,
            'nationality' => $this->nationality,
            'address_ar' => $this->addressAr,
            'address_en' => $this->addressEn,
            'expiry' => $this->expiry,
            'verification_level' => $this->verificationLevel,
            'meta' => $this->meta,
        ];
    }
}
