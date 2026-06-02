<?php

namespace App\Services\Identity;

/**
 * Contract every identity capture route implements (§5). Routes are pluggable:
 * PACI API, Hawyti consent flow, smart-card reader, MRZ+OCR. Resolve a reader
 * via the IdentityReaderManager (Laravel Manager pattern).
 */
interface IdentityReaderInterface
{
    /**
     * Read identity data from the supplied payload and normalise it.
     *
     * @param  array  $payload  Driver-specific input, e.g.:
     *                          - paci:      ['civil_id' => '...']
     *                          - hawyti:    ['session_id' => '...']
     *                          - smartcard: ['card_dump' => '...'] (parsed client-side, validated here)
     *                          - mrz_ocr:   ['mrz' => "...\n...", 'image' => <base64?>]
     *
     * @throws IdentityReadException
     */
    public function read(array $payload): IdentityData;

    /**
     * The driver key (paci | hawyti | smartcard | mrz_ocr | fake).
     */
    public function key(): string;
}
