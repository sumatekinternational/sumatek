<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identity reading (§5)
    |--------------------------------------------------------------------------
    |
    | Pluggable capture routes resolved through App\Services\Identity\
    | IdentityReaderInterface. Each agency may be permitted a subset of drivers
    | depending on the approvals/hardware they hold.
    |
    | Drivers: paci | hawyti | smartcard | mrz_ocr | fake
    |
    */

    'default' => env('IDENTITY_DEFAULT_DRIVER', 'fake'),

    // Verification level recorded against auto-filled records.
    // verified-PACI carries the highest trust; OCR is best-effort.
    'verification_levels' => [
        'paci' => 'verified',
        'hawyti' => 'verified',
        'smartcard' => 'verified',
        'mrz_ocr' => 'best_effort',
        'fake' => 'unverified',
    ],

    'drivers' => [

        'paci' => [
            'base_url' => env('PACI_BASE_URL'),
            'api_key' => env('PACI_API_KEY'),
            'client_id' => env('PACI_CLIENT_ID'),
            'timeout' => 10,
        ],

        'hawyti' => [
            'base_url' => env('HAWYTI_BASE_URL'),
            'api_key' => env('HAWYTI_API_KEY'),
            'qr_ttl' => 120, // seconds the consent QR stays valid
        ],

        'smartcard' => [
            // Card reads happen client-side; the server validates & normalises.
        ],

        'mrz_ocr' => [
            'base_url' => env('MRZ_OCR_BASE_URL'),
            'api_key' => env('MRZ_OCR_API_KEY'),
        ],

    ],

    // Raw capture images are deleted after this many minutes (§5/§9).
    'raw_image_retention_minutes' => (int) env('IDENTITY_RAW_RETENTION_MINUTES', 10),

];
