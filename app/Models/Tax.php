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

class Tax extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'rate_num',
        'rate_denom',
        'type',
        'is_compound',
        'is_recoverable',
        'account_id',
        'enabled',
    ];

    protected $casts = [
        'rate_num' => 'integer',
        'rate_denom' => 'integer',
        'is_compound' => 'boolean',
        'is_recoverable' => 'boolean',
        'enabled' => 'boolean',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(TaxRule::class);
    }

    public function documentItems(): HasMany
    {
        return $this->hasMany(DocumentItem::class);
    }

    // Scopes
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeRecoverable($query)
    {
        return $query->where('is_recoverable', true);
    }

    public function scopePercentage($query)
    {
        return $query->where('type', 'percentage');
    }

    public function scopeFixed($query)
    {
        return $query->where('type', 'fixed');
    }

    // Accessors
    public function getRateAttribute(): float
    {
        return $this->rate_num / $this->rate_denom;
    }

    public function getRatePercentAttribute(): float
    {
        return $this->rate * 100;
    }

    public function getDisplayRateAttribute(): string
    {
        if ($this->type === 'percentage') {
            return number_format($this->rate, 2) . '%';
        }
        return Money::fromFraction($this->rate_num, $this->rate_denom)->format();
    }

    // Methods
    public function calculate(Money $amount): Money
    {
        if ($this->type === 'fixed') {
            return Money::fromFraction($this->rate_num, $this->rate_denom);
        }

        // rate is already a decimal (e.g., 0.16 for 16%)
        $rate = $this->rate;
        $taxNum = (int) round($amount->getNumerator() * $rate);

        return Money::fromFraction($taxNum, $amount->getDenominator());
    }

    public function calculateInclusive(Money $totalInclusive): Money
    {
        if ($this->type === 'fixed') {
            return Money::fromFraction($this->rate_num, $this->rate_denom);
        }

        // rate is already a decimal (e.g., 0.16 for 16%)
        $rate = $this->rate;
        $taxNum = (int) round($totalInclusive->getNumerator() * $rate / (1 + $rate));

        return Money::fromFraction($taxNum, $totalInclusive->getDenominator());
    }
}
