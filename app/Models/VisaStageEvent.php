<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Append-only pipeline stage transition (§6.4). */
class VisaStageEvent extends Model
{
    use BelongsToTenant, HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id', 'visa_case_id', 'from_stage', 'to_stage', 'actor_user_id', 'note',
    ];
}
