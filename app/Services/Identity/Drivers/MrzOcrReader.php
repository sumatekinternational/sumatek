<?php

namespace App\Services\Identity\Drivers;

use App\Services\Identity\IdentityData;
use App\Services\Identity\IdentityReaderInterface;
use App\Services\Identity\IdentityReadException;
use App\Services\Identity\MrzParser;

/**
 * Passport MRZ + OCR route, all nationalities (§5.4). The mobile scan SDK
 * supplies the raw MRZ string; we validate checksums and normalise here. Raw
 * images are never persisted by this layer.
 */
class MrzOcrReader implements IdentityReaderInterface
{
    public function __construct(protected MrzParser $parser) {}

    public function read(array $payload): IdentityData
    {
        $mrz = $payload['mrz'] ?? null;

        if (! is_string($mrz) || trim($mrz) === '') {
            throw new IdentityReadException('An MRZ string is required for the mrz_ocr driver.');
        }

        return $this->parser->parse($mrz);
    }

    public function key(): string
    {
        return 'mrz_ocr';
    }
}
