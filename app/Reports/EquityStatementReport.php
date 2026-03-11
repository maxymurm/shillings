<?php

namespace App\Reports;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Company;
use App\ValueObjects\Money;
use Illuminate\Support\Carbon;

/**
 * Equity Statement (Statement of Changes in Equity) (Issue #104)
 *
 * Shows how equity changed during a period:
 * Opening equity + Net Income + Other changes = Closing equity
 */
class EquityStatementReport extends BaseReport
{
    public function getName(): string
    {
        return 'Statement of Changes in Equity';
    }

    public function getDescription(): string
    {
        return 'Shows changes in owner\'s equity during the period.';
    }

    /**
     * Generate the equity statement.
     */
    public function generate(): array
    {
        $company = $this->getCompany();
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        // Get opening equity balance (before start date)
        $openingEquity = $this->calculateEquityBalance($company, $startDate->copy()->subDay());

        // Calculate net income for the period
        $netIncome = $this->calculateNetIncome($company, $startDate, $endDate);

        // Get equity account transactions during period (investments, withdrawals)
        $equityChanges = $this->getEquityChanges($company, $startDate, $endDate);

        // Calculate closing equity
        $closingEquity = $openingEquity
            ->add($netIncome)
            ->add(Money::fromDecimal($equityChanges['total'], $this->getCurrencyCode()));

        $this->data = [
            'opening_equity' => $openingEquity->toDecimal(),
            'opening_equity_formatted' => $this->formatMoney($openingEquity->toDecimal()),
            'net_income' => $netIncome->toDecimal(),
            'net_income_formatted' => $this->formatMoney($netIncome->toDecimal()),
            'changes' => $equityChanges['items'],
            'total_changes' => $equityChanges['total'],
            'total_changes_formatted' => $this->formatMoney($equityChanges['total']),
            'closing_equity' => $closingEquity->toDecimal(),
            'closing_equity_formatted' => $this->formatMoney($closingEquity->toDecimal()),
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
        ];

        $this->generated = true;

        return $this->data;
    }

    /**
     * Calculate total equity balance as of a specific date.
     */
    protected function calculateEquityBalance(Company $company, Carbon $asOfDate): Money
    {
        $equityType = AccountType::where('name', 'Equity')->first();
        
        if (! $equityType) {
            return Money::zero($this->getCurrencyCode());
        }

        $accounts = Account::where('company_id', $company->id)
            ->where('account_type_id', $equityType->id)
            ->get();

        $totalEquity = Money::zero($this->getCurrencyCode());

        foreach ($accounts as $account) {
            $balance = $this->getAccountBalanceAsOf($account, $asOfDate);
            $totalEquity = $totalEquity->add($balance);
        }

        return $totalEquity;
    }

    /**
     * Calculate net income for the period.
     */
    protected function calculateNetIncome(Company $company, Carbon $startDate, Carbon $endDate): Money
    {
        // Revenue
        $incomeType = AccountType::where('name', 'INCOME')->first();
        $revenueAccounts = Account::where('company_id', $company->id)
            ->where('account_type_id', $incomeType->id)
            ->get();

        $totalRevenue = Money::zero($this->getCurrencyCode());
        foreach ($revenueAccounts as $account) {
            $periodActivity = $this->getAccountPeriodActivity($account, $startDate, $endDate);
            $totalRevenue = $totalRevenue->add($periodActivity);
        }

        // Expenses
        $expenseType = AccountType::where('name', 'EXPENSE')->first();
        $expenseAccounts = Account::where('company_id', $company->id)
            ->where('account_type_id', $expenseType->id)
            ->get();

        $totalExpenses = Money::zero($this->getCurrencyCode());
        foreach ($expenseAccounts as $account) {
            $periodActivity = $this->getAccountPeriodActivity($account, $startDate, $endDate);
            $totalExpenses = $totalExpenses->add($periodActivity);
        }

        // Net income = Revenue - Expenses
        return $totalRevenue->subtract($totalExpenses);
    }

    /**
     * Get direct equity account changes (investments, withdrawals).
     */
    protected function getEquityChanges(Company $company, Carbon $startDate, Carbon $endDate): array
    {
        $equityType = AccountType::where('name', 'EQUITY')->first();
        
        if (! $equityType) {
            return ['items' => [], 'total' => 0];
        }

        $accounts = Account::where('company_id', $company->id)
            ->where('account_type_id', $equityType->id)
            ->get();

        $changes = [];
        $total = 0;

        foreach ($accounts as $account) {
            $activity = $this->getAccountPeriodActivity($account, $startDate, $endDate);
            
            if (! $activity->isZero()) {
                $changes[] = [
                    'description' => "Direct changes to {$account->name}",
                    'amount' => $activity->toDecimal(),
                    'amount_formatted' => $this->formatMoney($activity->toDecimal()),
                ];
                $total += $activity->toDecimal();
            }
        }

        return [
            'items' => $changes,
            'total' => $total,
        ];
    }

    /**
     * Get account activity (net change) for a period.
     */
    protected function getAccountPeriodActivity(Account $account, Carbon $startDate, Carbon $endDate): Money
    {
        $transactions = $this->getAccountTransactions($account, $startDate, $endDate);

        $netActivity = Money::zero($this->getCurrencyCode());

        foreach ($transactions as $txn) {
            $amount = Money::fromFraction($txn->amount_num, $txn->amount_denom, $this->getCurrencyCode());

            if ($txn->action === 'DEBIT') {
                $netActivity = $netActivity->add($amount);
            } else {
                $netActivity = $netActivity->subtract($amount);
            }
        }

        return $netActivity;
    }

    // Reuse methods from GeneralLedgerReport
    protected function getAccountBalanceAsOf(Account $account, $asOfDate): Money
    {
        $result = \DB::table('splits')
            ->join('transactions', 'splits.transaction_id', '=', 'transactions.id')
            ->where('splits.account_id', $account->id)
            ->where('transactions.is_void', false)
            ->where('transactions.is_posted', true)
            ->where(function ($query) use ($asOfDate) {
                $query->whereDate('transactions.post_date', '<=', $asOfDate)
                    ->orWhere(function ($q) use ($asOfDate) {
                        $q->whereNull('transactions.post_date')
                            ->whereDate('transactions.transaction_date', '<=', $asOfDate);
                    });
            })
            ->selectRaw('
                SUM(CASE WHEN splits.action = ? THEN splits.amount_num ELSE -splits.amount_num END) as net_num,
                MAX(splits.amount_denom) as denom
            ', ['DEBIT'])
            ->first();

        if (! $result || ! $result->denom) {
            return Money::zero($this->getCurrencyCode());
        }

        return Money::fromFraction((int) ($result->net_num ?? 0), (int) $result->denom, $this->getCurrencyCode());
    }

    protected function getAccountTransactions(Account $account, $startDate, $endDate)
    {
        return \DB::table('splits')
            ->join('transactions', 'splits.transaction_id', '=', 'transactions.id')
            ->where('splits.account_id', $account->id)
            ->where('transactions.is_void', false)
            ->where('transactions.is_posted', true)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('transactions.post_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->whereNull('transactions.post_date')
                            ->whereBetween('transactions.transaction_date', [$startDate, $endDate]);
                    });
            })
            ->select([
                'splits.id as split_id',
                'splits.action',
                'splits.amount_num',
                'splits.amount_denom',
                'transactions.transaction_date',
            ])
            ->orderBy('transactions.post_date')
            ->orderBy('transactions.transaction_date')
            ->get();
    }
}
