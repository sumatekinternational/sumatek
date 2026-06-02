<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Versioned, append-only lifecycle event for a block: blocked / reviewed /
 * unblocked / disputed / moderated. Never updated or deleted. (§4)
 */
class BlockEvent extends Model
{
    use HasFactory;

    const UPDATED_AT = null; // append-only

    protected $fillable = [
        'block_id',
        'tenant_id',
        'actor_user_id',
        'type',       // blocked | reviewed | unblocked | disputed | upheld | removed
        'reason_code',
        'note',
        'snapshot',   // json snapshot of the block at the time
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
        ];
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(SponsorBlock::class, 'block_id');
    }
}
