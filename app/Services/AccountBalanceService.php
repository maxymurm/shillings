<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\Split;
use App\Models\Transaction;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Account Balance Service
 *
 * Manages cached account balances for performance optimization.
 * Updates balances on transaction post and provides recalculation.
 */
class AccountBalanceService
{
    /**
     * Get or create cached balance for an account.
     */
    public function getBalance(Account $account, ?string $currencyId = null): AccountBalance
    {
        $currencyId = $currencyId ?? $account->currency_id;

        return AccountBalance::firstOrCreate(
            [
                'account_id' => $account->id,
                'currency_id' => $currencyId,
            ],
            [
                'balance_num' => 0,
                'balance_denom' => 100,
                'debits_num' => 0,
                'debits_denom' => 100,
                'credits_num' => 0,
                'credits_denom' => 100,
                'split_count' => 0,
                'is_stale' => true, // Mark as stale until recalculated
            ]
        );
    }

    /**
     * Get balance as Money object.
     */
    public function getBalanceMoney(Account $account, ?string $currencyId = null): Money
    {
        $balance = $this->getBalance($account, $currencyId);

        if ($balance->is_stale) {
            $this->recalculateAccount($account, $currencyId);
            $balance->refresh();
        }

        return $balance->getBalanceMoney();
    }

    /**
     * Update balance when a transaction is posted.
     */
    public function onTransactionPosted(Transaction $transaction): void
    {
        $transaction->load('splits.account');

        foreach ($transaction->splits as $split) {
            $this->updateBalanceForSplit($split, $transaction);
        }
    }

    /**
     * Invalidate balances when a transaction is reversed or voided.
     */
    public function onTransactionInvalidated(Transaction $transaction): void
    {
        $accountIds = $transaction->splits()->pluck('account_id')->unique();

        AccountBalance::whereIn('account_id', $accountIds)
            ->update(['is_stale' => true]);
    }

    /**
     * Update balance for a single split.
     */
    private function updateBalanceForSplit(Split $split, Transaction $transaction): void
    {
        $currencyId = $split->value_currency_id ?? $transaction->currency_id;

        $balance = AccountBalance::firstOrNew([
            'account_id' => $split->account_id,
            'currency_id' => $currencyId,
        ]);

        // Initialize if new
        if (! $balance->exists) {
            $balance->balance_num = 0;
            $balance->balance_denom = $split->value_denom ?? 100;
            $balance->debits_num = 0;
            $balance->debits_denom = $split->value_denom ?? 100;
            $balance->credits_num = 0;
            $balance->credits_denom = $split->value_denom ?? 100;
            $balance->split_count = 0;
        }

        // Get split value as Money
        $splitMoney = $split->getValueMoney();

        // Update totals based on debit/credit
        $currentBalance = $balance->getBalanceMoney();
        $newBalance = $currentBalance->add($splitMoney);
        $balance->setBalanceFromMoney($newBalance);

        if ($splitMoney->isPositive()) {
            $currentDebits = $balance->getDebitsMoney();
            $newDebits = $currentDebits->add($splitMoney);
            $balance->setDebitsFromMoney($newDebits);
        } else {
            $currentCredits = $balance->getCreditsMoney();
            $newCredits = $currentCredits->add($splitMoney->negate());
            $balance->setCreditsFromMoney($newCredits);
        }

        $balance->split_count++;
        $balance->last_transaction_id = $transaction->id;
        $balance->last_transaction_date = $transaction->post_date ?? $transaction->date;
        $balance->calculated_at = now();
        $balance->is_stale = false;

        $balance->save();
    }

    /**
     * Recalculate balance for a specific account.
     */
    public function recalculateAccount(Account $account, ?string $currencyId = null): AccountBalance
    {
        $currencyId = $currencyId ?? $account->currency_id;

        // Get all posted splits for this account
        $splits = Split::query()
            ->where('account_id', $account->id)
            ->whereHas('transaction', function ($query) {
                $query->whereNotNull('posted_at')
                    ->where('is_void', false);
            })
            ->with('transaction')
            ->get();

        // Initialize totals
        $currency = $account->currency;
        $currencyCode = $currency?->code ?? 'KES';

        $totalBalance = Money::zero($currencyCode);
        $totalDebits = Money::zero($currencyCode);
        $totalCredits = Money::zero($currencyCode);
        $lastTransaction = null;
        $lastTransactionDate = null;
        $splitCount = 0;

        foreach ($splits as $split) {
            $splitMoney = $split->getValueMoney();

            $totalBalance = $totalBalance->add($splitMoney);

            if ($splitMoney->isPositive()) {
                $totalDebits = $totalDebits->add($splitMoney);
            } else {
                $totalCredits = $totalCredits->add($splitMoney->negate());
            }

            $transaction = $split->transaction;
            $transactionDate = $transaction->post_date ?? $transaction->date;

            if (! $lastTransactionDate || $transactionDate > $lastTransactionDate) {
                $lastTransaction = $transaction;
                $lastTransactionDate = $transactionDate;
            }

            $splitCount++;
        }

        // Update or create balance record
        $balance = AccountBalance::updateOrCreate(
            [
                'account_id' => $account->id,
                'currency_id' => $currencyId,
            ],
            [
                'balance_num' => $totalBalance->getNumerator(),
                'balance_denom' => $totalBalance->getDenominator(),
                'debits_num' => $totalDebits->getNumerator(),
                'debits_denom' => $totalDebits->getDenominator(),
                'credits_num' => $totalCredits->getNumerator(),
                'credits_denom' => $totalCredits->getDenominator(),
                'last_transaction_id' => $lastTransaction?->id,
                'last_transaction_date' => $lastTransactionDate,
                'split_count' => $splitCount,
                'calculated_at' => now(),
                'is_stale' => false,
            ]
        );

        Log::debug("Recalculated balance for account {$account->id}", [
            'balance' => $totalBalance->toDecimal(),
            'debits' => $totalDebits->toDecimal(),
            'credits' => $totalCredits->toDecimal(),
            'split_count' => $splitCount,
        ]);

        return $balance;
    }

    /**
     * Recalculate all balances for a company.
     */
    public function recalculateCompany(string $companyId): int
    {
        $accounts = Account::where('company_id', $companyId)->get();
        $recalculated = 0;

        foreach ($accounts as $account) {
            $this->recalculateAccount($account);
            $recalculated++;
        }

        Log::info("Recalculated balances for {$recalculated} accounts in company {$companyId}");

        return $recalculated;
    }

    /**
     * Recalculate all stale balances.
     */
    public function recalculateStale(): int
    {
        $staleBalances = AccountBalance::stale()->with('account')->get();
        $recalculated = 0;

        foreach ($staleBalances as $balance) {
            if ($balance->account) {
                $this->recalculateAccount($balance->account, $balance->currency_id);
                $recalculated++;
            }
        }

        Log::info("Recalculated {$recalculated} stale balances");

        return $recalculated;
    }

    /**
     * Invalidate all balances (force full recalculation).
     */
    public function invalidateAll(?string $companyId = null): int
    {
        $query = AccountBalance::query();

        if ($companyId) {
            $accountIds = Account::where('company_id', $companyId)->pluck('id');
            $query->whereIn('account_id', $accountIds);
        }

        return $query->update(['is_stale' => true]);
    }

    /**
     * Get balance statistics for reporting.
     */
    public function getBalanceStatistics(?string $companyId = null): array
    {
        $query = AccountBalance::query();

        if ($companyId) {
            $accountIds = Account::where('company_id', $companyId)->pluck('id');
            $query->whereIn('account_id', $accountIds);
        }

        return [
            'total_accounts' => $query->count(),
            'stale_accounts' => (clone $query)->where('is_stale', true)->count(),
            'fresh_accounts' => (clone $query)->where('is_stale', false)->count(),
            'total_splits' => $query->sum('split_count'),
            'last_calculated' => $query->max('calculated_at'),
        ];
    }
}
