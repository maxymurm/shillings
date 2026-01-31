<?php

namespace App\Models;

use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'from_currency_id',
        'to_currency_id',
        'rate_num',
        'rate_denom',
        'effective_date',
        'source',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rate_num' => 'integer',
        'rate_denom' => 'integer',
        'effective_date' => 'date',
    ];

    /**
     * Default denominator for rate precision (6 decimal places).
     */
    public const DEFAULT_DENOM = 1_000_000;

    /**
     * The source currency.
     */
    public function fromCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'from_currency_id');
    }

    /**
     * The target currency.
     */
    public function toCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'to_currency_id');
    }

    /**
     * Get rate as decimal.
     */
    public function getRate(): float
    {
        return $this->rate_num / $this->rate_denom;
    }

    /**
     * Get rate as Money value object.
     */
    public function getRateMoney(): Money
    {
        return Money::fromFraction($this->rate_num, $this->rate_denom);
    }

    /**
     * Get the inverse rate (to -> from).
     */
    public function getInverseRate(): float
    {
        return $this->rate_denom / $this->rate_num;
    }

    /**
     * Convert an amount using this rate.
     */
    public function convert(Money $amount): Money
    {
        return $amount->convertTo(
            $this->toCurrency->code,
            $this->getRateMoney()
        );
    }

    /**
     * Create rate from decimal value.
     */
    public static function fromDecimalRate(
        string $fromCurrencyId,
        string $toCurrencyId,
        float $rate,
        string $effectiveDate,
        ?string $source = 'manual'
    ): self {
        return new self([
            'from_currency_id' => $fromCurrencyId,
            'to_currency_id' => $toCurrencyId,
            'rate_num' => (int) round($rate * self::DEFAULT_DENOM),
            'rate_denom' => self::DEFAULT_DENOM,
            'effective_date' => $effectiveDate,
            'source' => $source,
        ]);
    }

    /**
     * Scope to get rates for a currency pair.
     */
    public function scopeForPair($query, string $fromCurrencyId, string $toCurrencyId)
    {
        return $query->where('from_currency_id', $fromCurrencyId)
            ->where('to_currency_id', $toCurrencyId);
    }

    /**
     * Scope to get rates on or before a date.
     */
    public function scopeOnOrBefore($query, $date)
    {
        return $query->where('effective_date', '<=', $date);
    }

    /**
     * Scope to get the most recent rate.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('effective_date', 'desc');
    }
}
