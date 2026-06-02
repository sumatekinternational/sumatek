<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Pii;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Domestic worker / CV-bank entry, keyed on passport (§6.2).
 *
 * Availability lifecycle: available | reserved | deployed | returned | departed
 */
class Worker extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    public const STATUSES = ['available', 'reserved', 'deployed', 'returned', 'departed'];

    protected $fillable = [
        'tenant_id',
        'passport_no',
        'passport_no_hash',
        'passport_expiry',
        'name_ar',
        'name_en',
        'nationality',
        'date_of_birth',
        'sex',
        'photo_path',
        'skills',
        'languages',
        'experience_years',
        'medical_status',
        'status',
        'source_agency',
        'verification_level',
        'identity_source',
    ];

    protected $hidden = ['passport_no_hash'];

    protected function casts(): array
    {
        return [
            'passport_no' => 'encrypted',
            'passport_expiry' => 'date',
            'date_of_birth' => 'date',
            'skills' => 'array',
            'languages' => 'array',
            'experience_years' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Worker $worker) {
            if ($worker->isDirty('passport_no')) {
                $worker->passport_no_hash = Pii::hash($worker->passport_no);
            }
        });
    }
}
