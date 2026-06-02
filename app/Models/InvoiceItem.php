<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'invoice_id', 'description_en', 'description_ar',
        'quantity', 'unit_price', 'amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'amount' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Keep line amount = quantity * unit_price.
        static::saving(function (InvoiceItem $item) {
            $item->amount = $item->quantity * $item->unit_price;
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
