<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountType extends Model
{
    use HasFactory;
    use HasUuids;

    // Standard account type names
    public const ASSET = 'ASSET';

    public const LIABILITY = 'LIABILITY';

    public const EQUITY = 'EQUITY';

    public const INCOME = 'INCOME';

    public const EXPENSE = 'EXPENSE';

    // Normal balance constants
    public const DEBIT = 'DEBIT';

    public const CREDIT = 'CREDIT';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'normal_balance',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Accounts of this type.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Check if this account type has a debit normal balance.
     */
    public function isDebitNormal(): bool
    {
        return $this->normal_balance === self::DEBIT;
    }

    /**
     * Check if this account type has a credit normal balance.
     */
    public function isCreditNormal(): bool
    {
        return $this->normal_balance === self::CREDIT;
    }

    /**
     * Check if this is an asset type.
     */
    public function isAsset(): bool
    {
        return $this->name === self::ASSET;
    }

    /**
     * Check if this is a liability type.
     */
    public function isLiability(): bool
    {
        return $this->name === self::LIABILITY;
    }

    /**
     * Check if this is an equity type.
     */
    public function isEquity(): bool
    {
        return $this->name === self::EQUITY;
    }

    /**
     * Check if this is an income type.
     */
    public function isIncome(): bool
    {
        return $this->name === self::INCOME;
    }

    /**
     * Check if this is an expense type.
     */
    public function isExpense(): bool
    {
        return $this->name === self::EXPENSE;
    }

    /**
     * Get the multiplier for calculating balance direction.
     * Returns 1 for debit-normal accounts, -1 for credit-normal accounts.
     */
    public function getBalanceMultiplier(): int
    {
        return $this->isDebitNormal() ? 1 : -1;
    }
}
