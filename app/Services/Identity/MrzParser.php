<?php

namespace App\Services\Identity;

/**
 * ICAO 9303 Machine-Readable Zone parser with check-digit validation (§5).
 * Supports TD-3 (passport, 2x44) and TD-1 (ID card, 3x30). This is a genuine
 * parser — an OCR/scan SDK supplies the raw MRZ string; we validate & extract.
 */
class MrzParser
{
    /** @var array<string,int> */
    protected const WEIGHTS = [7, 3, 1];

    public function parse(string $mrz): IdentityData
    {
        $lines = array_values(array_filter(
            array_map(fn ($l) => strtoupper(trim($l)), preg_split('/\r\n|\r|\n/', $mrz)),
            fn ($l) => $l !== ''
        ));

        return match (count($lines)) {
            2 => $this->parseTd3($lines),
            3 => $this->parseTd1($lines),
            default => throw new IdentityReadException('Unsupported MRZ format: expected 2 (TD-3) or 3 (TD-1) lines.'),
        };
    }

    /** Passport (TD-3): two lines of 44 characters. */
    protected function parseTd3(array $lines): IdentityData
    {
        [$l1, $l2] = [str_pad($lines[0], 44, '<'), str_pad($lines[1], 44, '<')];

        $names = $this->names(substr($l1, 5));

        $number = $this->clean(substr($l2, 0, 9));
        $numberCd = substr($l2, 9, 1);
        $nationality = $this->clean(substr($l2, 10, 3));
        $dob = substr($l2, 13, 6);
        $dobCd = substr($l2, 19, 1);
        $sex = substr($l2, 20, 1);
        $expiry = substr($l2, 21, 6);
        $expiryCd = substr($l2, 27, 1);

        $checks = [
            'number' => $this->checkDigit(substr($l2, 0, 9)) === $numberCd,
            'dob' => $this->checkDigit($dob) === $dobCd,
            'expiry' => $this->checkDigit($expiry) === $expiryCd,
        ];

        return new IdentityData(
            documentType: 'passport',
            number: $number,
            nameEn: $names,
            dateOfBirth: $this->date($dob, isBirth: true),
            sex: $this->sex($sex),
            nationality: $nationality,
            expiry: $this->date($expiry, isBirth: false),
            verificationLevel: 'best_effort',
            meta: ['checksums' => $checks, 'mrz_valid' => ! in_array(false, $checks, true)],
        );
    }

    /** ID card (TD-1): three lines of 30 characters. */
    protected function parseTd1(array $lines): IdentityData
    {
        [$l1, $l2, $l3] = [
            str_pad($lines[0], 30, '<'),
            str_pad($lines[1], 30, '<'),
            str_pad($lines[2], 30, '<'),
        ];

        $number = $this->clean(substr($l1, 5, 9));
        $numberCd = substr($l1, 14, 1);
        $dob = substr($l2, 0, 6);
        $dobCd = substr($l2, 6, 1);
        $sex = substr($l2, 7, 1);
        $expiry = substr($l2, 8, 6);
        $expiryCd = substr($l2, 14, 1);
        $nationality = $this->clean(substr($l2, 15, 3));
        $names = $this->names($l3);

        $checks = [
            'number' => $this->checkDigit(substr($l1, 5, 9)) === $numberCd,
            'dob' => $this->checkDigit($dob) === $dobCd,
            'expiry' => $this->checkDigit($expiry) === $expiryCd,
        ];

        return new IdentityData(
            documentType: 'civil_id',
            number: $number,
            nameEn: $names,
            dateOfBirth: $this->date($dob, isBirth: true),
            sex: $this->sex($sex),
            nationality: $nationality,
            expiry: $this->date($expiry, isBirth: false),
            verificationLevel: 'best_effort',
            meta: ['checksums' => $checks, 'mrz_valid' => ! in_array(false, $checks, true)],
        );
    }

    protected function names(string $field): string
    {
        // Surname<<Given<Names — '<<' separates surname from given names.
        [$surname, $given] = array_pad(explode('<<', $field, 2), 2, '');
        $surname = trim(str_replace('<', ' ', $surname));
        $given = trim(str_replace('<', ' ', $given));

        return trim("$given $surname");
    }

    protected function clean(string $value): string
    {
        return trim(str_replace('<', '', $value));
    }

    protected function sex(string $value): ?string
    {
        return match ($value) {
            'M' => 'M',
            'F' => 'F',
            default => null,
        };
    }

    /** YYMMDD -> Y-m-d, windowing the 2-digit year sensibly. */
    protected function date(string $yymmdd, bool $isBirth): ?string
    {
        if (! preg_match('/^\d{6}$/', $yymmdd)) {
            return null;
        }

        $yy = (int) substr($yymmdd, 0, 2);
        $mm = substr($yymmdd, 2, 2);
        $dd = substr($yymmdd, 4, 2);

        // Birth dates are in the past; expiry dates are near-future.
        $century = $isBirth
            ? ($yy > (int) date('y') ? 1900 : 2000)
            : 2000;

        return sprintf('%04d-%s-%s', $century + $yy, $mm, $dd);
    }

    /** ICAO 9303 check digit over the 7-3-1 weighting. */
    public function checkDigit(string $value): string
    {
        $sum = 0;
        $chars = str_split($value);

        foreach ($chars as $i => $char) {
            $sum += $this->charValue($char) * self::WEIGHTS[$i % 3];
        }

        return (string) ($sum % 10);
    }

    protected function charValue(string $char): int
    {
        if ($char === '<') {
            return 0;
        }
        if (ctype_digit($char)) {
            return (int) $char;
        }

        return ord($char) - 55; // A=10 .. Z=35
    }
}
