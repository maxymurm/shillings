<?php

namespace App\Models;

use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Split extends Model
{
    use HasFactory;
    use HasUuids;

    // Action constants
    public const DEBIT = 'DEBIT';

    public const CREDIT = 'CREDIT';

    // Reconciliation state constants
    public const RECONCILED_NOT = 'n';      // Not reconciled

    public const RECONCILED_CLEARED = 'c';  // Cleared but not reconciled

    public const RECONCILED_YES = 'y';      // Reconciled

    public const RECONCILED_FROZEN = 'f';   // Frozen (cannot be changed)

    public const RECONCILED_VOID = 'v';     // Voided

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_id',
        'account_id',
        'tax_id',
        'amount_num',
        'amount_denom',
        'value_num',
        'value_denom',
        'action',
        'memo',
        'reconciled_state',
        'reconcile_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount_num' => 'integer',
        'amount_denom' => 'integer',
        'value_num' => 'integer',
        'value_denom' => 'integer',
        'reconcile_date' => 'date',
    ];

    /**
     * The transaction this split belongs to.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * The account this split affects.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * The tax this split is associated with.
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * Get the amount as a decimal (in account's currency).
     */
    public function getAmountAttribute(): float
    {
        if ($this->amount_denom == 0) {
            return 0;
        }

        return $this->amount_num / $this->amount_denom;
    }

    /**
     * Set the amount from a decimal value.
     */
    public function setAmountAttribute(float $value): void
    {
        // Default to 2 decimal places (100 as denominator)
        $denom = 100;

        // Calculate numerator to preserve precision
        $this->attributes['amount_num'] = (int) round($value * $denom);
        $this->attributes['amount_denom'] = $denom;
    }

    /**
     * Get the value as a decimal (in transaction's currency).
     */
    public function getValueAttribute(): float
    {
        if ($this->value_denom == 0) {
            return 0;
        }

        return $this->value_num / $this->value_denom;
    }

    /**
     * Set the value from a decimal value.
     */
    public function setValueAttribute(float $value): void
    {
        // Default to 2 decimal places (100 as denominator)
        $denom = 100;

        // Calculate numerator to preserve precision
        $this->attributes['value_num'] = (int) round($value * $denom);
        $this->attributes['value_denom'] = $denom;
    }

    /**
     * Get the signed amount (positive for debit, negative for credit).
     */
    public function getSignedAmount(): float
    {
        $amount = $this->amount;

        return $this->isDebit() ? $amount : -$amount;
    }

    /**
     * Get the signed value (positive for debit, negative for credit).
     */
    public function getSignedValue(): float
    {
        $value = $this->value;

        return $this->isDebit() ? $value : -$value;
    }

    /**
     * Check if this is a debit split.
     */
    public function isDebit(): bool
    {
        return $this->action === self::DEBIT;
    }

    /**
     * Check if this is a credit split.
     */
    public function isCredit(): bool
    {
        return $this->action === self::CREDIT;
    }

    /**
     * Check if this split is reconciled.
     */
    public function isReconciled(): bool
    {
        return $this->reconciled_state === self::RECONCILED_YES;
    }

    /**
     * Check if this split is cleared.
     */
    public function isCleared(): bool
    {
        return in_array($this->reconciled_state, [
            self::RECONCILED_CLEARED,
            self::RECONCILED_YES,
            self::RECONCILED_FROZEN,
        ]);
    }

    /**
     * Mark as cleared.
     */
    public function clear(): self
    {
        $this->reconciled_state = self::RECONCILED_CLEARED;
        $this->save();

        return $this;
    }

    /**
     * Mark as reconciled.
     */
    public function reconcile(): self
    {
        $this->reconciled_state = self::RECONCILED_YES;
        $this->reconcile_date = now();
        $this->save();

        return $this;
    }

    /**
     * Unreconcile (set to not reconciled).
     */
    public function unreconcile(): self
    {
        $this->reconciled_state = self::RECONCILED_NOT;
        $this->reconcile_date = null;
        $this->save();

        return $this;
    }

    /**
     * Scope to get only debit splits.
     */
    public function scopeDebits($query)
    {
        return $query->where('action', self::DEBIT);
    }

    /**
     * Scope to get only credit splits.
     */
    public function scopeCredits($query)
    {
        return $query->where('action', self::CREDIT);
    }

    /**
     * Scope to get only reconciled splits.
     */
    public function scopeReconciled($query)
    {
        return $query->where('reconciled_state', self::RECONCILED_YES);
    }

    /**
     * Scope to get only unreconciled splits.
     */
    public function scopeUnreconciled($query)
    {
        return $query->where('reconciled_state', self::RECONCILED_NOT);
    }

    /**
     * Scope to get splits for a specific account.
     */
    public function scopeForAccount($query, string $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Set amount with custom precision (denominator).
     */
    public function setAmountWithPrecision(int $numerator, int $denominator): self
    {
        $this->amount_num = $numerator;
        $this->amount_denom = $denominator;

        return $this;
    }

    /**
     * Set value with custom precision (denominator).
     */
    public function setValueWithPrecision(int $numerator, int $denominator): self
    {
        $this->value_num = $numerator;
        $this->value_denom = $denominator;

        return $this;
    }

    /**
     * Get the display-friendly amount with sign.
     */
    public function getDisplayAmountAttribute(): string
    {
        $amount = abs($this->amount);
        $sign = $this->isDebit() ? '+' : '-';

        return "{$sign}{$amount}";
    }

    /**
     * Get the value as a Money object.
     *
     * Returns signed value: positive for debits, negative for credits.
     */
    public function getValueMoney(): Money
    {
        $currencyCode = $this->transaction?->currency?->code ?? 'KES';

        $money = Money::fromFraction(
            $this->value_num ?? $this->amount_num,
            $this->value_denom ?? $this->amount_denom,
            $currencyCode
        );

        // Return signed value: positive for debit, negative for credit
        return $this->isDebit() ? $money : $money->negate();
    }

    /**
     * Get the amount as a Money object.
     *
     * Returns signed amount: positive for debits, negative for credits.
     */
    public function getAmountMoney(): Money
    {
        $currencyCode = $this->account?->currency?->code ?? 'KES';

        $money = Money::fromFraction(
            $this->amount_num,
            $this->amount_denom,
            $currencyCode
        );

        // Return signed amount: positive for debit, negative for credit
        return $this->isDebit() ? $money : $money->negate();
    }
}
