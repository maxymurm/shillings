<?php

namespace App\Models;

use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cached account balance for performance optimization.
 *
 * @property string $id
 * @property string $account_id
 * @property string $currency_id
 * @property int $balance_num
 * @property int $balance_denom
 * @property int $debits_num
 * @property int $debits_denom
 * @property int $credits_num
 * @property int $credits_denom
 * @property string|null $last_transaction_id
 * @property \Carbon\Carbon|null $last_transaction_date
 * @property int $split_count
 * @property \Carbon\Carbon|null $calculated_at
 * @property bool $is_stale
 */
class AccountBalance extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'account_id',
        'currency_id',
        'balance_num',
        'balance_denom',
        'debits_num',
        'debits_denom',
        'credits_num',
        'credits_denom',
        'last_transaction_id',
        'last_transaction_date',
        'split_count',
        'calculated_at',
        'is_stale',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'balance_num' => 'integer',
        'balance_denom' => 'integer',
        'debits_num' => 'integer',
        'debits_denom' => 'integer',
        'credits_num' => 'integer',
        'credits_denom' => 'integer',
        'split_count' => 'integer',
        'last_transaction_date' => 'datetime',
        'calculated_at' => 'datetime',
        'is_stale' => 'boolean',
    ];

    /**
     * The account this balance belongs to.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * The currency for this balance.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * The last transaction included in this balance.
     */
    public function lastTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'last_transaction_id');
    }

    /**
     * Get the balance as a Money value object.
     */
    public function getBalanceMoney(): Money
    {
        $currencyCode = $this->currency?->code ?? 'KES';

        return Money::fromFraction($this->balance_num, $this->balance_denom, $currencyCode);
    }

    /**
     * Get the total debits as a Money value object.
     */
    public function getDebitsMoney(): Money
    {
        $currencyCode = $this->currency?->code ?? 'KES';

        return Money::fromFraction($this->debits_num, $this->debits_denom, $currencyCode);
    }

    /**
     * Get the total credits as a Money value object.
     */
    public function getCreditsMoney(): Money
    {
        $currencyCode = $this->currency?->code ?? 'KES';

        return Money::fromFraction($this->credits_num, $this->credits_denom, $currencyCode);
    }

    /**
     * Get the balance as a decimal.
     */
    public function getBalance(): float
    {
        return $this->balance_denom !== 0
            ? $this->balance_num / $this->balance_denom
            : 0.0;
    }

    /**
     * Get total debits as decimal.
     */
    public function getDebits(): float
    {
        return $this->debits_denom !== 0
            ? $this->debits_num / $this->debits_denom
            : 0.0;
    }

    /**
     * Get total credits as decimal.
     */
    public function getCredits(): float
    {
        return $this->credits_denom !== 0
            ? $this->credits_num / $this->credits_denom
            : 0.0;
    }

    /**
     * Mark this balance as stale (needs recalculation).
     */
    public function markStale(): void
    {
        $this->is_stale = true;
        $this->save();
    }

    /**
     * Update balance from a Money value object.
     */
    public function setBalanceFromMoney(Money $balance): void
    {
        $this->balance_num = $balance->getNumerator();
        $this->balance_denom = $balance->getDenominator();
    }

    /**
     * Update debits from a Money value object.
     */
    public function setDebitsFromMoney(Money $debits): void
    {
        $this->debits_num = $debits->getNumerator();
        $this->debits_denom = $debits->getDenominator();
    }

    /**
     * Update credits from a Money value object.
     */
    public function setCreditsFromMoney(Money $credits): void
    {
        $this->credits_num = $credits->getNumerator();
        $this->credits_denom = $credits->getDenominator();
    }

    /**
     * Scope: get stale balances that need recalculation.
     */
    public function scopeStale($query)
    {
        return $query->where('is_stale', true);
    }

    /**
     * Scope: get balances for a specific account.
     */
    public function scopeForAccount($query, string $accountId)
    {
        return $query->where('account_id', $accountId);
    }
}
