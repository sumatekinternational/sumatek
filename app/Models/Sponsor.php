<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Pii;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sponsor (employer), keyed on Civil ID. (§6.1)
 *
 *  - civil_id is encrypted at rest (§9).
 *  - civil_id_hash is a keyed hash used for lookup/dedupe and to query the
 *    cross-agency block registry (§4).
 */
class Sponsor extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'civil_id',
        'civil_id_hash',
        'name_ar',
        'name_en',
        'date_of_birth',
        'sex',
        'nationality',
        'address_ar',
        'address_en',
        'phone',
        'email',
        'verification_level', // verified | best_effort | unverified
        'identity_source',    // paci | hawyti | smartcard | mrz_ocr | manual
        'consent_captured_at',
    ];

    protected $hidden = ['civil_id_hash'];

    protected function casts(): array
    {
        return [
            'civil_id' => 'encrypted',
            'address_ar' => 'encrypted',
            'address_en' => 'encrypted',
            'date_of_birth' => 'date',
            'consent_captured_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Keep the lookup hash in sync whenever the civil id changes.
        static::saving(function (Sponsor $sponsor) {
            if ($sponsor->isDirty('civil_id')) {
                $sponsor->civil_id_hash = Pii::hash($sponsor->civil_id);
            }
        });
    }
}
