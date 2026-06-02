<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An entry in the CENTRAL, CROSS-TENANT sponsor block registry (§4).
 *
 * IMPORTANT: this table is owned by the vendor and is deliberately NOT
 * tenant-scoped — that cross-agency visibility is the whole point. Each row
 * records WHICH agency (blocking_tenant_id) asserted the block. All reads must
 * go through App\Services\BlockRegistry\* so governance/visibility rules and
 * audit logging are applied consistently.
 */
class SponsorBlock extends Model
{
    use HasFactory;

    protected $table = 'sponsor_block_registry';

    protected $fillable = [
        'blocking_tenant_id',
        'civil_id',
        'civil_id_hash',
        'sponsor_name_ar',
        'sponsor_name_en',
        'reason_code',
        'note',
        'evidence_path',
        'status',          // active | revoked | expired
        'review_at',
        'created_by_user_id',
        'revoked_by_user_id',
        'revoked_reason',
        'revoked_at',
        'moderation_state', // none | flagged | upheld | removed
        'moderated_by_user_id',
        'consent_captured_at',
    ];

    protected $hidden = ['civil_id_hash'];

    protected function casts(): array
    {
        return [
            'civil_id' => 'encrypted',
            'review_at' => 'datetime',
            'revoked_at' => 'datetime',
            'consent_captured_at' => 'datetime',
        ];
    }

    public function blockingTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'blocking_tenant_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(BlockEvent::class, 'block_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->moderation_state !== 'removed';
    }
}
