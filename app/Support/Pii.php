<?php

namespace App\Support;

/**
 * Helpers for handling personally-identifiable identifiers (Civil ID, passport
 * number). Sensitive values are stored encrypted at rest for display, plus a
 * deterministic keyed hash in a *_hash column so we can look records up and
 * dedupe without decrypting or exposing the raw value. (§5/§9)
 */
class Pii
{
    /**
     * Normalise then keyed-hash an identifier for equality lookups.
     * Uses the app key as a pepper so hashes are not portable across installs.
     */
    public static function hash(?string $value): ?string
    {
        $normalized = static::normalize($value);

        if ($normalized === null) {
            return null;
        }

        return hash_hmac('sha256', $normalized, static::pepper());
    }

    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Strip whitespace and any non-alphanumeric separators, upper-case.
        $value = preg_replace('/[^A-Za-z0-9]/', '', $value) ?? '';

        return $value === '' ? null : strtoupper($value);
    }

    protected static function pepper(): string
    {
        $key = config('app.key');

        return str_starts_with((string) $key, 'base64:')
            ? base64_decode(substr($key, 7))
            : (string) $key;
    }
}
