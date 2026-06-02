<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** A legacy data import job (§6.6). */
class DataImport extends Model
{
    use BelongsToTenant, HasFactory;

    public const TYPES = ['sponsors', 'workers'];

    protected $fillable = [
        'tenant_id', 'user_id', 'type', 'source', 'original_filename', 'file_path',
        'status', 'dedupe_strategy', 'mapping', 'stats', 'errors', 'created_ids', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'mapping' => 'array',
            'stats' => 'array',
            'errors' => 'array',
            'created_ids' => 'array',
            'completed_at' => 'datetime',
        ];
    }
}
