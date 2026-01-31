<?php

namespace App\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Money Value Object
 *
 * Represents monetary values using numerator/denominator fractions
 * to avoid floating-point precision issues (GnuCash-style).
 *
 * @example
 * $money = Money::fromDecimal('100.50', 'USD');
 * $money = Money::fromFraction(10050, 100);
 * $result = $money->add(Money::fromDecimal('50.25'));
 */
final class Money implements JsonSerializable, Stringable
{
    /**
     * Default denominator for storage (100 = 2 decimal places)
     */
    public const DEFAULT_DENOM = 100;

    /**
     * Maximum safe denominator to prevent overflow
     */
    public const MAX_DENOM = 1_000_000_000;

    private function __construct(
        private readonly int $numerator,
        private readonly int $denominator,
        private readonly ?string $currencyCode = null
    ) {
        if ($this->denominator <= 0) {
            throw new InvalidArgumentException('Denominator must be positive');
        }
        if ($this->denominator > self::MAX_DENOM) {
            throw new InvalidArgumentException('Denominator exceeds maximum safe value');
        }
    }

    /**
     * Create Money from numerator and denominator.
     */
    public static function fromFraction(int $numerator, int $denominator, ?string $currencyCode = null): self
    {
        return new self($numerator, $denominator, $currencyCode);
    }

    /**
     * Create Money from a decimal string or float.
     */
    public static function fromDecimal(string|float $amount, ?string $currencyCode = null, int $precision = 2): self
    {
        $denominator = (int) pow(10, $precision);
        $numerator = (int) round((float) $amount * $denominator);

        return new self($numerator, $denominator, $currencyCode);
    }

    /**
     * Create Money from cents/smallest unit.
     */
    public static function fromCents(int $cents, ?string $currencyCode = null): self
    {
        return new self($cents, 100, $currencyCode);
    }

    /**
     * Create zero Money.
     */
    public static function zero(?string $currencyCode = null, int $denominator = self::DEFAULT_DENOM): self
    {
        return new self(0, $denominator, $currencyCode);
    }

    /**
     * Get the numerator.
     */
    public function getNumerator(): int
    {
        return $this->numerator;
    }

    /**
     * Get the denominator.
     */
    public function getDenominator(): int
    {
        return $this->denominator;
    }

    /**
     * Get the currency code.
     */
    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    /**
     * Convert to decimal float for display.
     */
    public function toDecimal(): float
    {
        return $this->numerator / $this->denominator;
    }

    /**
     * Format as string with specified decimal places.
     */
    public function format(int $decimals = 2, string $decimalSeparator = '.', string $thousandsSeparator = ','): string
    {
        return number_format($this->toDecimal(), $decimals, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Add two Money values.
     */
    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);

        // Find common denominator
        $commonDenom = $this->lcm($this->denominator, $other->denominator);

        $thisMultiplier = $commonDenom / $this->denominator;
        $otherMultiplier = $commonDenom / $other->denominator;

        $newNumerator = ($this->numerator * $thisMultiplier) + ($other->numerator * $otherMultiplier);

        return new self($newNumerator, $commonDenom, $this->currencyCode);
    }

    /**
     * Subtract Money value.
     */
    public function subtract(Money $other): self
    {
        return $this->add($other->negate());
    }

    /**
     * Multiply by a scalar.
     */
    public function multiply(int|float $multiplier): self
    {
        if (is_float($multiplier)) {
            // Convert float multiplier to fraction
            $precision = strlen(substr(strrchr((string) $multiplier, '.'), 1));
            $multiplierDenom = (int) pow(10, $precision);
            $multiplierNum = (int) round($multiplier * $multiplierDenom);

            $newNumerator = $this->numerator * $multiplierNum;
            $newDenominator = $this->denominator * $multiplierDenom;

            return (new self($newNumerator, $newDenominator, $this->currencyCode))->simplify();
        }

        return new self($this->numerator * $multiplier, $this->denominator, $this->currencyCode);
    }

    /**
     * Divide by a scalar.
     */
    public function divide(int|float $divisor): self
    {
        if ($divisor == 0) {
            throw new InvalidArgumentException('Division by zero');
        }

        if (is_float($divisor)) {
            // Convert float divisor to fraction and multiply by reciprocal
            $precision = strlen(substr(strrchr((string) $divisor, '.'), 1));
            $divisorDenom = (int) pow(10, $precision);
            $divisorNum = (int) round($divisor * $divisorDenom);

            // Multiply by reciprocal
            $newNumerator = $this->numerator * $divisorDenom;
            $newDenominator = $this->denominator * $divisorNum;

            return (new self($newNumerator, $newDenominator, $this->currencyCode))->simplify();
        }

        return new self($this->numerator, $this->denominator * $divisor, $this->currencyCode);
    }

    /**
     * Negate the value (flip sign).
     */
    public function negate(): self
    {
        return new self(-$this->numerator, $this->denominator, $this->currencyCode);
    }

    /**
     * Get absolute value.
     */
    public function abs(): self
    {
        return new self(abs($this->numerator), $this->denominator, $this->currencyCode);
    }

    /**
     * Check if zero.
     */
    public function isZero(): bool
    {
        return $this->numerator === 0;
    }

    /**
     * Check if positive.
     */
    public function isPositive(): bool
    {
        return $this->numerator > 0;
    }

    /**
     * Check if negative.
     */
    public function isNegative(): bool
    {
        return $this->numerator < 0;
    }

    /**
     * Check equality with another Money.
     */
    public function equals(Money $other): bool
    {
        $this->assertSameCurrency($other);

        // Cross multiply to compare
        return ($this->numerator * $other->denominator) === ($other->numerator * $this->denominator);
    }

    /**
     * Compare with another Money. Returns -1, 0, or 1.
     */
    public function compare(Money $other): int
    {
        $this->assertSameCurrency($other);

        $left = $this->numerator * $other->denominator;
        $right = $other->numerator * $this->denominator;

        return $left <=> $right;
    }

    /**
     * Check if greater than another Money.
     */
    public function greaterThan(Money $other): bool
    {
        return $this->compare($other) > 0;
    }

    /**
     * Check if greater than or equal to another Money.
     */
    public function greaterThanOrEqual(Money $other): bool
    {
        return $this->compare($other) >= 0;
    }

    /**
     * Check if less than another Money.
     */
    public function lessThan(Money $other): bool
    {
        return $this->compare($other) < 0;
    }

    /**
     * Check if less than or equal to another Money.
     */
    public function lessThanOrEqual(Money $other): bool
    {
        return $this->compare($other) <= 0;
    }

    /**
     * Simplify the fraction by dividing by GCD.
     */
    public function simplify(): self
    {
        $gcd = $this->gcd(abs($this->numerator), $this->denominator);

        if ($gcd <= 1) {
            return $this;
        }

        return new self(
            (int) ($this->numerator / $gcd),
            (int) ($this->denominator / $gcd),
            $this->currencyCode
        );
    }

    /**
     * Round to specified decimal places.
     */
    public function round(int $precision = 2, int $mode = PHP_ROUND_HALF_UP): self
    {
        $targetDenom = (int) pow(10, $precision);
        $decimal = round($this->toDecimal(), $precision, $mode);
        $numerator = (int) round($decimal * $targetDenom);

        return new self($numerator, $targetDenom, $this->currencyCode);
    }

    /**
     * Allocate money across recipients (handles rounding).
     *
     * @param  array<int>  $ratios
     * @return array<Money>
     */
    public function allocate(array $ratios): array
    {
        $total = array_sum($ratios);
        $remainder = $this->numerator;
        $results = [];

        foreach ($ratios as $ratio) {
            $share = (int) floor($this->numerator * $ratio / $total);
            $results[] = new self($share, $this->denominator, $this->currencyCode);
            $remainder -= $share;
        }

        // Distribute remainder
        for ($i = 0; $i < $remainder; $i++) {
            $results[$i] = new self(
                $results[$i]->numerator + 1,
                $this->denominator,
                $this->currencyCode
            );
        }

        return $results;
    }

    /**
     * Convert to different currency using exchange rate.
     */
    public function convertTo(string $targetCurrency, Money $exchangeRate): self
    {
        $convertedNumerator = $this->numerator * $exchangeRate->numerator;
        $convertedDenominator = $this->denominator * $exchangeRate->denominator;

        return (new self($convertedNumerator, $convertedDenominator, $targetCurrency))->simplify();
    }

    /**
     * Assert currencies match for operations.
     */
    private function assertSameCurrency(Money $other): void
    {
        if ($this->currencyCode !== null && $other->currencyCode !== null) {
            if ($this->currencyCode !== $other->currencyCode) {
                throw new InvalidArgumentException(
                    "Currency mismatch: {$this->currencyCode} vs {$other->currencyCode}"
                );
            }
        }
    }

    /**
     * Calculate Greatest Common Divisor.
     */
    private function gcd(int $a, int $b): int
    {
        while ($b !== 0) {
            $temp = $b;
            $b = $a % $b;
            $a = $temp;
        }

        return $a;
    }

    /**
     * Calculate Least Common Multiple.
     */
    private function lcm(int $a, int $b): int
    {
        return (int) (abs($a * $b) / $this->gcd($a, $b));
    }

    /**
     * Serialize for JSON.
     */
    public function jsonSerialize(): array
    {
        return [
            'numerator' => $this->numerator,
            'denominator' => $this->denominator,
            'currency_code' => $this->currencyCode,
            'decimal' => $this->toDecimal(),
            'formatted' => $this->format(),
        ];
    }

    /**
     * String representation.
     */
    public function __toString(): string
    {
        $formatted = $this->format();

        return $this->currencyCode
            ? "{$this->currencyCode} {$formatted}"
            : $formatted;
    }
}
