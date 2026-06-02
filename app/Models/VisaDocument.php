<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisaDocument extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'visa_case_id', 'stage', 'key',
        'label_en', 'label_ar', 'required', 'status', 'file_path', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function visaCase(): BelongsTo
    {
        return $this->belongsTo(VisaCase::class);
    }
}
