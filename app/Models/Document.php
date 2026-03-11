<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Document extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'type',
        'document_number',
        'order_number',
        'contact_id',
        'status',
        'issued_at',
        'due_at',
        'currency_code',
        'exchange_rate_num',
        'exchange_rate_denom',
        'subtotal_num',
        'subtotal_denom',
        'tax_total_num',
        'tax_total_denom',
        'discount_num',
        'discount_denom',
        'total_num',
        'total_denom',
        'amount_paid_num',
        'amount_paid_denom',
        'notes',
        'footer',
        'bill_term_id',
        'parent_id',
        'created_by',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'due_at' => 'date',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'paid_at' => 'datetime',
        'exchange_rate_num' => 'integer',
        'exchange_rate_denom' => 'integer',
        'subtotal_num' => 'integer',
        'subtotal_denom' => 'integer',
        'tax_total_num' => 'integer',
        'tax_total_denom' => 'integer',
        'discount_num' => 'integer',
        'discount_denom' => 'integer',
        'total_num' => 'integer',
        'total_denom' => 'integer',
        'amount_paid_num' => 'integer',
        'amount_paid_denom' => 'integer',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentItem::class)->orderBy('sort_order');
    }

    public function billTerm(): BelongsTo
    {
        return $this->belongsTo(BillTerm::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Document::class, 'parent_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeInvoices($query)
    {
        return $query->where('type', 'invoice');
    }

    public function scopeBills($query)
    {
        return $query->where('type', 'bill');
    }

    public function scopeQuotes($query)
    {
        return $query->where('type', 'quote');
    }

    public function scopeCreditNotes($query)
    {
        return $query->where('type', 'credit_note');
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSent($query)
    {
        return $query->whereIn('status', ['sent', 'viewed']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->where('due_at', '<', now());
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['sent', 'viewed', 'partial', 'overdue']);
    }

    // Accessors
    public function getSubtotalAttribute(): Money
    {
        return Money::fromFraction($this->subtotal_num ?? 0, $this->subtotal_denom ?? 100);
    }

    public function getTaxTotalAttribute(): Money
    {
        return Money::fromFraction($this->tax_total_num ?? 0, $this->tax_total_denom ?? 100);
    }

    public function getDiscountAttribute(): Money
    {
        return Money::fromFraction($this->discount_num ?? 0, $this->discount_denom ?? 100);
    }

    public function getTotalAttribute(): Money
    {
        return Money::fromFraction($this->total_num ?? 0, $this->total_denom ?? 100);
    }

    public function getAmountPaidAttribute(): Money
    {
        return Money::fromFraction($this->amount_paid_num ?? 0, $this->amount_paid_denom ?? 100);
    }

    public function getAmountDueAttribute(): Money
    {
        return $this->total->subtract($this->amount_paid);
    }

    // Methods for tests compatibility
    public function getTotal(): Money
    {
        return $this->total;
    }

    public function getPaidAmount(): Money
    {
        return $this->amount_paid;
    }

    public function isEditable(): bool
    {
        return $this->is_editable;
    }

    public function getExchangeRateAttribute(): float
    {
        return $this->exchange_rate_num / $this->exchange_rate_denom;
    }

    public function getIsOverdueAttribute(): bool
    {
        return !in_array($this->status, ['paid', 'cancelled']) 
            && $this->due_at->isPast();
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'paid';
    }

    public function getIsDraftAttribute(): bool
    {
        return $this->status === 'draft';
    }

    public function getIsEditableAttribute(): bool
    {
        return $this->status === 'draft';
    }

    // Methods
    public function calculateTotals(): void
    {
        $subtotal = Money::zero();
        $taxTotal = Money::zero();

        foreach ($this->items as $item) {
            $subtotal = $subtotal->add($item->subtotal);
            $taxTotal = $taxTotal->add($item->tax_total);
        }

        $discount = Money::fromFraction($this->discount_num ?? 0, $this->discount_denom ?? 100);
        $total = $subtotal->add($taxTotal)->subtract($discount);

        $this->subtotal_num = $subtotal->getNumerator();
        $this->subtotal_denom = $subtotal->getDenominator();
        $this->tax_total_num = $taxTotal->getNumerator();
        $this->tax_total_denom = $taxTotal->getDenominator();
        $this->total_num = $total->getNumerator();
        $this->total_denom = $total->getDenominator();
    }

    public function markAsSent(): void
    {
        $this->status = 'sent';
        $this->sent_at = now();
        $this->save();
    }

    public function markAsViewed(): void
    {
        if ($this->status === 'sent') {
            $this->status = 'viewed';
        }
        $this->viewed_at = now();
        $this->save();
    }

    public function markAsPaid(): void
    {
        $this->status = 'paid';
        $this->amount_paid_num = $this->total_num;
        $this->amount_paid_denom = $this->total_denom;
        $this->paid_at = now();
        $this->save();
    }

    public function recordPayment(Money $amount): void
    {
        $newPaid = $this->amount_paid->add($amount);
        
        $this->amount_paid_num = $newPaid->getNumerator();
        $this->amount_paid_denom = $newPaid->getDenominator();

        if ($newPaid->greaterThanOrEqual($this->total)) {
            $this->status = 'paid';
            $this->paid_at = now();
        } elseif ($newPaid->greaterThan(Money::zero())) {
            $this->status = 'partial';
        }

        $this->save();
    }

    public function cancel(string $reason = null): void
    {
        $this->status = 'cancelled';
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Cancelled: {$reason}";
        }
        $this->save();
    }
}
