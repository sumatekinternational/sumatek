<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Agency -> Sponsor recruitment-fee invoice (§6.5). */
class Invoice extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    public const STATUSES = ['draft', 'issued', 'partially_paid', 'paid', 'overdue', 'void'];

    protected $fillable = [
        'tenant_id', 'sponsor_id', 'contract_id', 'invoice_no', 'status',
        'currency', 'subtotal', 'tax', 'total', 'amount_paid',
        'issued_at', 'due_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'tax' => 'integer',
            'total' => 'integer',
            'amount_paid' => 'integer',
            'issued_at' => 'datetime',
            'due_at' => 'date',
        ];
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function balance(): int
    {
        return max(0, $this->total - $this->amount_paid);
    }

    /** Recompute totals from items and reconcile paid status. */
    public function recalculate(): void
    {
        $subtotal = (int) $this->items()->sum('amount');
        $this->subtotal = $subtotal;
        $this->total = $subtotal + (int) $this->tax;
        $this->amount_paid = (int) $this->payments()->where('status', 'captured')->sum('amount');
        $this->reconcileStatus();
        $this->save();
    }

    public function reconcileStatus(): void
    {
        if (in_array($this->status, ['draft', 'void'], true)) {
            return;
        }

        if ($this->amount_paid >= $this->total && $this->total > 0) {
            $this->status = 'paid';
        } elseif ($this->amount_paid > 0) {
            $this->status = 'partially_paid';
        } elseif ($this->due_at && $this->due_at->endOfDay()->isPast()) {
            $this->status = 'overdue';
        } else {
            $this->status = 'issued';
        }
    }
}
