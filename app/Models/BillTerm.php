<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class BillTerm extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'due_days',
        'discount_days',
        'discount_percent_num',
        'discount_percent_denom',
        'is_default',
        'enabled',
    ];

    protected $casts = [
        'due_days' => 'integer',
        'discount_days' => 'integer',
        'discount_percent_num' => 'integer',
        'discount_percent_denom' => 'integer',
        'is_default' => 'boolean',
        'enabled' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function calculateDueDate(Carbon $issuedAt): Carbon
    {
        return $issuedAt->copy()->addDays($this->due_days);
    }

    public function calculateDiscountDate(Carbon $issuedAt): ?Carbon
    {
        if (!$this->discount_days) {
            return null;
        }

        return $issuedAt->copy()->addDays($this->discount_days);
    }

    public function getDiscountAmount(Money $total): Money
    {
        if (!$this->discount_percent_num || !$this->discount_percent_denom) {
            return Money::fromFraction(0, 100);
        }

        $discountRate = $this->discount_percent_num / $this->discount_percent_denom / 100;
        $discountNum = (int) round($total->getNumerator() * $discountRate);

        return Money::fromFraction($discountNum, $total->getDenominator());
    }

    public function getDiscountPercentAttribute(): float
    {
        if (!$this->discount_percent_num || !$this->discount_percent_denom) {
            return 0;
        }

        return $this->discount_percent_num / $this->discount_percent_denom;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (BillTerm $term) {
            if ($term->is_default) {
                static::where('company_id', $term->company_id)
                    ->where('id', '!=', $term->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}
