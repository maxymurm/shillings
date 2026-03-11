<?php

namespace App\Models;

use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentItem extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'document_id',
        'name',
        'description',
        'account_id',
        'quantity',
        'price_num',
        'price_denom',
        'discount_type',
        'discount_num',
        'discount_denom',
        'tax_id',
        'subtotal_num',
        'subtotal_denom',
        'tax_total_num',
        'tax_total_denom',
        'total_num',
        'total_denom',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'price_num' => 'integer',
        'price_denom' => 'integer',
        'discount_num' => 'integer',
        'discount_denom' => 'integer',
        'subtotal_num' => 'integer',
        'subtotal_denom' => 'integer',
        'tax_total_num' => 'integer',
        'tax_total_denom' => 'integer',
        'total_num' => 'integer',
        'total_denom' => 'integer',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    // Accessors
    public function getPriceAttribute(): Money
    {
        return Money::fromFraction($this->price_num ?? 0, $this->price_denom ?? 100);
    }

    public function getDiscountAttribute(): Money
    {
        return Money::fromFraction($this->discount_num ?? 0, $this->discount_denom ?? 100);
    }

    public function getSubtotalAttribute(): Money
    {
        return Money::fromFraction($this->subtotal_num ?? 0, $this->subtotal_denom ?? 100);
    }

    public function getTaxTotalAttribute(): Money
    {
        return Money::fromFraction($this->tax_total_num ?? 0, $this->tax_total_denom ?? 100);
    }

    public function getTotalAttribute(): Money
    {
        return Money::fromFraction($this->total_num ?? 0, $this->total_denom ?? 100);
    }

    // Methods
    public function calculateTotals(): void
    {
        // Calculate line subtotal (quantity * price)
        $lineSubtotal = (int) round($this->quantity * $this->price_num);
        
        // Apply discount
        $discountAmount = 0;
        if ($this->discount_num > 0) {
            if ($this->discount_type === 'percentage') {
                $discountRate = $this->discount_num / $this->discount_denom / 100;
                $discountAmount = (int) round($lineSubtotal * $discountRate);
            } else {
                $discountAmount = $this->discount_num;
            }
        }
        
        $subtotalAfterDiscount = $lineSubtotal - $discountAmount;
        
        // Calculate tax
        $taxAmount = 0;
        if ($this->tax_id && $this->tax) {
            $taxRate = $this->tax->rate_num / $this->tax->rate_denom / 100;
            $taxAmount = (int) round($subtotalAfterDiscount * $taxRate);
        }
        
        $this->subtotal_num = $subtotalAfterDiscount;
        $this->subtotal_denom = $this->price_denom;
        $this->tax_total_num = $taxAmount;
        $this->tax_total_denom = $this->price_denom;
        $this->total_num = $subtotalAfterDiscount + $taxAmount;
        $this->total_denom = $this->price_denom;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (DocumentItem $item) {
            $item->calculateTotals();
        });

        static::saved(function (DocumentItem $item) {
            if ($item->document) {
                $item->document->calculateTotals();
                $item->document->save();
            }
        });

        static::deleted(function (DocumentItem $item) {
            if ($item->document) {
                $item->document->calculateTotals();
                $item->document->save();
            }
        });
    }
}
