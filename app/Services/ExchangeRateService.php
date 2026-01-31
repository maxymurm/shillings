<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\ValueObjects\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Exchange Rate Service
 *
 * Handles currency conversion and exchange rate lookups.
 */
class ExchangeRateService
{
    /**
     * Cache TTL in seconds (1 hour).
     */
    private const CACHE_TTL = 3600;

    /**
     * Get exchange rate for a currency pair on a specific date.
     */
    public function getRate(
        string|Currency $fromCurrency,
        string|Currency $toCurrency,
        ?Carbon $date = null
    ): ?ExchangeRate {
        $fromId = $fromCurrency instanceof Currency ? $fromCurrency->id : $fromCurrency;
        $toId = $toCurrency instanceof Currency ? $toCurrency->id : $toCurrency;
        $date = $date ?? now();

        // Same currency, no conversion needed
        if ($fromId === $toId) {
            return ExchangeRate::fromDecimalRate($fromId, $toId, 1.0, $date->toDateString(), 'identity');
        }

        $cacheKey = "exchange_rate:{$fromId}:{$toId}:{$date->toDateString()}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($fromId, $toId, $date) {
            // Try direct rate
            $rate = ExchangeRate::forPair($fromId, $toId)
                ->onOrBefore($date)
                ->latest()
                ->first();

            if ($rate) {
                return $rate;
            }

            // Try inverse rate
            $inverseRate = ExchangeRate::forPair($toId, $fromId)
                ->onOrBefore($date)
                ->latest()
                ->first();

            if ($inverseRate) {
                // Create inverse rate object (not persisted)
                return new ExchangeRate([
                    'from_currency_id' => $fromId,
                    'to_currency_id' => $toId,
                    'rate_num' => $inverseRate->rate_denom,
                    'rate_denom' => $inverseRate->rate_num,
                    'effective_date' => $inverseRate->effective_date,
                    'source' => $inverseRate->source . ' (inverse)',
                ]);
            }

            return null;
        });
    }

    /**
     * Get the decimal exchange rate value.
     */
    public function getRateValue(
        string|Currency $fromCurrency,
        string|Currency $toCurrency,
        ?Carbon $date = null
    ): ?float {
        $rate = $this->getRate($fromCurrency, $toCurrency, $date);

        return $rate?->getRate();
    }

    /**
     * Convert a Money amount to another currency.
     */
    public function convert(
        Money $amount,
        string|Currency $toCurrency,
        ?Carbon $date = null
    ): ?Money {
        $fromCurrencyCode = $amount->getCurrencyCode();
        if (! $fromCurrencyCode) {
            throw new \InvalidArgumentException('Money amount must have a currency code for conversion');
        }

        // Look up currency by code
        $fromCurrency = Cache::remember(
            "currency:code:{$fromCurrencyCode}",
            self::CACHE_TTL,
            fn () => Currency::where('code', $fromCurrencyCode)->first()
        );

        if (! $fromCurrency) {
            throw new \InvalidArgumentException("Unknown currency: {$fromCurrencyCode}");
        }

        $toCurrencyModel = $toCurrency instanceof Currency
            ? $toCurrency
            : Currency::find($toCurrency);

        if (! $toCurrencyModel) {
            throw new \InvalidArgumentException('Target currency not found');
        }

        // Same currency
        if ($fromCurrency->id === $toCurrencyModel->id) {
            return Money::fromFraction(
                $amount->getNumerator(),
                $amount->getDenominator(),
                $toCurrencyModel->code
            );
        }

        $rate = $this->getRate($fromCurrency, $toCurrencyModel, $date);

        if (! $rate) {
            return null; // No rate available
        }

        return $amount->convertTo($toCurrencyModel->code, $rate->getRateMoney());
    }

    /**
     * Store a new exchange rate.
     */
    public function storeRate(
        string|Currency $fromCurrency,
        string|Currency $toCurrency,
        float $rate,
        ?Carbon $date = null,
        string $source = 'manual'
    ): ExchangeRate {
        $fromId = $fromCurrency instanceof Currency ? $fromCurrency->id : $fromCurrency;
        $toId = $toCurrency instanceof Currency ? $toCurrency->id : $toCurrency;
        $date = $date ?? now();

        $exchangeRate = ExchangeRate::create([
            'from_currency_id' => $fromId,
            'to_currency_id' => $toId,
            'rate_num' => (int) round($rate * ExchangeRate::DEFAULT_DENOM),
            'rate_denom' => ExchangeRate::DEFAULT_DENOM,
            'effective_date' => $date->toDateString(),
            'source' => $source,
        ]);

        // Clear cache
        $this->clearCache($fromId, $toId, $date);

        return $exchangeRate;
    }

    /**
     * Update or create rate for a currency pair on a date.
     */
    public function updateOrCreateRate(
        string|Currency $fromCurrency,
        string|Currency $toCurrency,
        float $rate,
        ?Carbon $date = null,
        string $source = 'manual'
    ): ExchangeRate {
        $fromId = $fromCurrency instanceof Currency ? $fromCurrency->id : $fromCurrency;
        $toId = $toCurrency instanceof Currency ? $toCurrency->id : $toCurrency;
        $date = $date ?? now();

        $exchangeRate = ExchangeRate::updateOrCreate(
            [
                'from_currency_id' => $fromId,
                'to_currency_id' => $toId,
                'effective_date' => $date->toDateString(),
            ],
            [
                'rate_num' => (int) round($rate * ExchangeRate::DEFAULT_DENOM),
                'rate_denom' => ExchangeRate::DEFAULT_DENOM,
                'source' => $source,
            ]
        );

        // Clear cache
        $this->clearCache($fromId, $toId, $date);

        return $exchangeRate;
    }

    /**
     * Get all rates for a date.
     */
    public function getRatesForDate(Carbon $date): array
    {
        return ExchangeRate::where('effective_date', $date->toDateString())
            ->with(['fromCurrency', 'toCurrency'])
            ->get()
            ->all();
    }

    /**
     * Get rate history for a currency pair.
     */
    public function getRateHistory(
        string|Currency $fromCurrency,
        string|Currency $toCurrency,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $fromId = $fromCurrency instanceof Currency ? $fromCurrency->id : $fromCurrency;
        $toId = $toCurrency instanceof Currency ? $toCurrency->id : $toCurrency;

        $query = ExchangeRate::forPair($fromId, $toId);

        if ($startDate) {
            $query->where('effective_date', '>=', $startDate->toDateString());
        }

        if ($endDate) {
            $query->where('effective_date', '<=', $endDate->toDateString());
        }

        return $query->orderBy('effective_date', 'asc')->get()->all();
    }

    /**
     * Clear cached rate.
     */
    private function clearCache(string $fromId, string $toId, Carbon $date): void
    {
        Cache::forget("exchange_rate:{$fromId}:{$toId}:{$date->toDateString()}");
        Cache::forget("exchange_rate:{$toId}:{$fromId}:{$date->toDateString()}");
    }
}
