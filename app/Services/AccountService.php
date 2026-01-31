<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Currency;
use App\Models\Split;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AccountService
{
    /**
     * Get the balance of an account as of a specific date.
     *
     * @param  Account  $account  The account to get balance for
     * @param  Carbon|string|null  $date  The date to calculate balance as of (null = current)
     * @return array{numerator: int, denominator: int, decimal: float}
     */
    public function getBalance(Account $account, Carbon|string|null $date = null): array
    {
        $date = $date ? Carbon::parse($date) : now();

        // Get all splits for this account up to the date
        $splits = Split::query()
            ->where('account_id', $account->id)
            ->whereHas('transaction', function ($query) use ($date) {
                $query->where('transaction_date', '<=', $date)
                    ->where('is_posted', true);
            })
            ->get();

        return $this->calculateBalanceFromSplits($splits, $account);
    }

    /**
     * Get the balance in a different currency.
     *
     * @param  Account  $account  The account
     * @param  Currency  $targetCurrency  The currency to convert to
     * @param  Carbon|string|null  $date  The date for balance and exchange rate
     * @param  float|null  $exchangeRate  Optional specific exchange rate to use
     * @return array{numerator: int, denominator: int, decimal: float}
     */
    public function getBalanceInCurrency(
        Account $account,
        Currency $targetCurrency,
        Carbon|string|null $date = null,
        ?float $exchangeRate = null
    ): array {
        $balance = $this->getBalance($account, $date);

        // If account is already in target currency, return as-is
        if ($account->currency_id === $targetCurrency->id) {
            return $balance;
        }

        // Apply exchange rate (default to 1:1 if not provided)
        $rate = $exchangeRate ?? 1.0;

        $convertedDecimal = $balance['decimal'] * $rate;
        $denominator = 100; // Use standard precision
        $numerator = (int) round($convertedDecimal * $denominator);

        return [
            'numerator' => $numerator,
            'denominator' => $denominator,
            'decimal' => $convertedDecimal,
        ];
    }

    /**
     * Get the total balance of an account and all its descendants.
     *
     * @param  Account  $account  The parent account
     * @param  Carbon|string|null  $date  The date to calculate balances as of
     * @return array{numerator: int, denominator: int, decimal: float}
     */
    public function getChildrenBalances(Account $account, Carbon|string|null $date = null): array
    {
        $totalNumerator = 0;
        $denominator = 100; // Normalize to common denominator

        // Get own balance
        $ownBalance = $this->getBalance($account, $date);
        $totalNumerator += (int) round($ownBalance['decimal'] * $denominator);

        // Get all descendants' balances
        foreach ($account->getDescendants() as $descendant) {
            $childBalance = $this->getBalance($descendant, $date);
            // TODO: Handle currency conversion if child is in different currency
            $totalNumerator += (int) round($childBalance['decimal'] * $denominator);
        }

        return [
            'numerator' => $totalNumerator,
            'denominator' => $denominator,
            'decimal' => $totalNumerator / $denominator,
        ];
    }

    /**
     * Get running balance for account register display.
     *
     * @param  Account  $account  The account
     * @param  Carbon|string|null  $startDate  Start date filter
     * @param  Carbon|string|null  $endDate  End date filter
     * @return Collection<int, array{split: Split, running_balance: float}>
     */
    public function getRunningBalance(
        Account $account,
        Carbon|string|null $startDate = null,
        Carbon|string|null $endDate = null
    ): Collection {
        $query = Split::query()
            ->where('account_id', $account->id)
            ->whereHas('transaction', function ($q) use ($startDate, $endDate) {
                $q->where('is_posted', true);
                if ($startDate) {
                    $q->where('transaction_date', '>=', Carbon::parse($startDate));
                }
                if ($endDate) {
                    $q->where('transaction_date', '<=', Carbon::parse($endDate));
                }
            })
            ->with(['transaction'])
            ->join('transactions', 'splits.transaction_id', '=', 'transactions.id')
            ->orderBy('transactions.transaction_date')
            ->orderBy('splits.created_at')
            ->select('splits.*');

        $splits = $query->get();
        $runningBalance = 0;
        $accountType = $account->accountType;

        return $splits->map(function (Split $split) use (&$runningBalance, $accountType) {
            // For debit-normal accounts (assets, expenses): debits increase, credits decrease
            // For credit-normal accounts (liabilities, equity, income): credits increase, debits decrease
            $amount = $split->value;

            if ($accountType->isDebitNormal()) {
                $runningBalance += $split->isDebit() ? $amount : -$amount;
            } else {
                $runningBalance += $split->isCredit() ? $amount : -$amount;
            }

            return [
                'split' => $split,
                'running_balance' => $runningBalance,
            ];
        });
    }

    /**
     * Calculate balance from a collection of splits.
     *
     * @param  Collection<int, Split>  $splits  The splits to sum
     * @param  Account  $account  The account (for determining debit/credit direction)
     * @return array{numerator: int, denominator: int, decimal: float}
     */
    protected function calculateBalanceFromSplits(Collection $splits, Account $account): array
    {
        $totalNumerator = 0;
        $denominator = 100; // Normalize to common denominator

        $accountType = $account->accountType;

        foreach ($splits as $split) {
            // Normalize to common denominator
            $normalizedAmount = (int) round($split->value * $denominator);

            // For debit-normal accounts: debits add, credits subtract
            // For credit-normal accounts: credits add, debits subtract
            if ($accountType->isDebitNormal()) {
                $totalNumerator += $split->isDebit() ? $normalizedAmount : -$normalizedAmount;
            } else {
                $totalNumerator += $split->isCredit() ? $normalizedAmount : -$normalizedAmount;
            }
        }

        return [
            'numerator' => $totalNumerator,
            'denominator' => $denominator,
            'decimal' => $totalNumerator / $denominator,
        ];
    }

    /**
     * Get account balance as a simple decimal value.
     */
    public function getBalanceDecimal(Account $account, Carbon|string|null $date = null): float
    {
        return $this->getBalance($account, $date)['decimal'];
    }

    /**
     * Get accounts with balances for a report.
     *
     * @param  string  $companyId  The company ID
     * @param  string|null  $accountType  Filter by account type name
     * @param  Carbon|string|null  $date  As of date
     * @return Collection<int, array{account: Account, balance: float}>
     */
    public function getAccountsWithBalances(
        string $companyId,
        ?string $accountType = null,
        Carbon|string|null $date = null
    ): Collection {
        $query = Account::query()
            ->where('company_id', $companyId)
            ->where('is_placeholder', false)
            ->with(['accountType', 'currency']);

        if ($accountType) {
            $query->whereHas('accountType', function ($q) use ($accountType) {
                $q->where('name', $accountType);
            });
        }

        return $query->get()->map(function (Account $account) use ($date) {
            return [
                'account' => $account,
                'balance' => $this->getBalanceDecimal($account, $date),
            ];
        });
    }

    /**
     * Calculate the trial balance for a company.
     *
     * @param  string  $companyId  The company ID
     * @param  Carbon|string|null  $date  As of date
     * @return array{debits: float, credits: float, balanced: bool, accounts: Collection}
     */
    public function getTrialBalance(string $companyId, Carbon|string|null $date = null): array
    {
        $accounts = $this->getAccountsWithBalances($companyId, null, $date);

        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($accounts as $item) {
            $balance = $item['balance'];
            $accountType = $item['account']->accountType;

            if ($accountType->isDebitNormal()) {
                if ($balance >= 0) {
                    $totalDebits += $balance;
                } else {
                    $totalCredits += abs($balance);
                }
            } else {
                if ($balance >= 0) {
                    $totalCredits += $balance;
                } else {
                    $totalDebits += abs($balance);
                }
            }
        }

        return [
            'debits' => $totalDebits,
            'credits' => $totalCredits,
            'balanced' => abs($totalDebits - $totalCredits) < 0.01, // Allow for rounding
            'accounts' => $accounts,
        ];
    }
}
