<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Audit of every identity auto-read (§5/§9). Stores the source driver and
 * verification level but never the raw capture image.
 */
class IdentityRead extends Model
{
    use BelongsToTenant, HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'driver',             // paci | hawyti | smartcard | mrz_ocr | fake
        'document_type',      // civil_id | passport
        'verification_level',
        'subject_hash',       // keyed hash of the captured identifier
        'succeeded',
        'meta',               // non-PII metadata (card validity, checksum pass)
    ];

    protected function casts(): array
    {
        return [
            'succeeded' => 'boolean',
            'meta' => 'array',
        ];
    }
}
