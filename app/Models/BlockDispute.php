<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dispute raised against a registry block (§4). Cross-tenant: not scoped to a
 * single agency, because a dispute may concern another agency's block.
 */
class BlockDispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'block_id', 'tenant_id', 'raised_by_user_id', 'disputant_type', 'reason',
        'status', 'resolution_note', 'resolved_by_user_id', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(SponsorBlock::class, 'block_id');
    }
}
