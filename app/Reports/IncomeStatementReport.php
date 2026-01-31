<?php

namespace App\Reports;

use App\Models\Account;
use App\Models\Split;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;

/**
 * Income Statement Report (Profit & Loss)
 *
 * Shows Revenue - Expenses = Net Income for a period.
 */
class IncomeStatementReport extends BaseReport
{
    public function getName(): string
    {
        return 'Income Statement';
    }

    public function getDescription(): string
    {
        return 'Profit & Loss statement showing Revenue, Expenses, and Net Income for a period.';
    }

    /**
     * Generate the income statement report.
     */
    public function generate(): array
    {
        $company = $this->getCompany();
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        // Get income accounts with balances for period
        $income = $this->getAccountsWithBalances('Income', $startDate, $endDate);

        // Get expense accounts with balances for period
        $expenses = $this->getAccountsWithBalances('Expense', $startDate, $endDate);

        // Calculate totals
        $totalIncome = $this->sumSection($income);
        $totalExpenses = $this->sumSection($expenses);
        $netIncome = $totalIncome->subtract($totalExpenses);

        // Calculate percentages
        $incomeWithPercentages = $this->addPercentages($income, $totalIncome);
        $expensesWithPercentages = $this->addPercentages($expenses, $totalIncome);

        $this->data = [
            'income' => [
                'label' => 'Revenue / Income',
                'accounts' => $incomeWithPercentages,
                'total' => $totalIncome->toDecimal(),
                'total_formatted' => $this->formatMoney($totalIncome->toDecimal()),
            ],
            'expenses' => [
                'label' => 'Expenses',
                'accounts' => $expensesWithPercentages,
                'total' => $totalExpenses->toDecimal(),
                'total_formatted' => $this->formatMoney($totalExpenses->toDecimal()),
            ],
            'net_income' => [
                'label' => 'Net Income',
                'amount' => $netIncome->toDecimal(),
                'amount_formatted' => $this->formatMoney($netIncome->toDecimal()),
                'is_profit' => $netIncome->isPositive(),
                'percentage_of_income' => $totalIncome->isZero() ? 0 : round(($netIncome->toDecimal() / $totalIncome->toDecimal()) * 100, 2),
            ],
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
        ];

        $this->generated = true;

        return $this->data;
    }

    /**
     * Get accounts with balances for a period.
     */
    private function getAccountsWithBalances(string $typeName, $startDate, $endDate): array
    {
        $company = $this->getCompany();

        // Get top-level accounts of this type
        $accounts = Account::query()
            ->where('company_id', $company->id)
            ->whereHas('accountType', fn ($q) => $q->where('name', $typeName))
            ->whereNull('parent_id')
            ->with(['children', 'accountType'])
            ->orderBy('code')
            ->get();

        return $this->buildAccountTree($accounts, $startDate, $endDate);
    }

    /**
     * Build hierarchical account tree with balances.
     */
    private function buildAccountTree($accounts, $startDate, $endDate, int $depth = 0): array
    {
        $result = [];

        foreach ($accounts as $account) {
            $children = [];

            if ($account->children->isNotEmpty()) {
                $children = $this->buildAccountTree($account->children, $startDate, $endDate, $depth + 1);
            }

            // Calculate balance for period
            $balance = $this->getAccountBalance($account, $startDate, $endDate);
            $childrenTotal = Money::zero($this->getCurrencyCode());

            foreach ($children as $child) {
                $childrenTotal = $childrenTotal->add(
                    Money::fromDecimal($child['balance'], $this->getCurrencyCode())
                );
            }

            $totalBalance = $account->is_placeholder
                ? $childrenTotal
                : $balance->add($childrenTotal);

            // Skip zero-balance branches
            if ($totalBalance->isZero() && empty($children)) {
                continue;
            }

            $result[] = [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'depth' => $depth,
                'is_placeholder' => $account->is_placeholder,
                'balance' => $totalBalance->toDecimal(),
                'balance_formatted' => $this->formatMoney($totalBalance->toDecimal()),
                'children' => $children,
            ];
        }

        return $result;
    }

    /**
     * Get account activity for a period.
     */
    private function getAccountBalance(Account $account, $startDate, $endDate): Money
    {
        if ($account->is_placeholder) {
            return Money::zero($this->getCurrencyCode());
        }

        $result = DB::table('splits')
            ->join('transactions', 'splits.transaction_id', '=', 'transactions.id')
            ->where('splits.account_id', $account->id)
            ->where('transactions.is_void', false)
            ->whereNotNull('transactions.posted_at')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where(function ($q) use ($startDate, $endDate) {
                    $q->whereDate('transactions.post_date', '>=', $startDate)
                        ->whereDate('transactions.post_date', '<=', $endDate);
                })->orWhere(function ($q) use ($startDate, $endDate) {
                    $q->whereNull('transactions.post_date')
                        ->whereDate('transactions.date', '>=', $startDate)
                        ->whereDate('transactions.date', '<=', $endDate);
                });
            })
            ->selectRaw('
                SUM(CASE WHEN splits.action = ? THEN splits.amount_num ELSE -splits.amount_num END) as net_num,
                MAX(splits.amount_denom) as denom
            ', [Split::DEBIT])
            ->first();

        if (! $result || ! $result->denom) {
            return Money::zero($this->getCurrencyCode());
        }

        // For income accounts (credit-normal), negate to show positive income
        // For expense accounts (debit-normal), keep as is
        $net = (int) ($result->net_num ?? 0);
        $accountType = $account->accountType->name ?? '';

        if ($accountType === 'Income') {
            $net = -$net; // Income is credit-normal, so negate
        }

        return Money::fromFraction($net, (int) $result->denom, $this->getCurrencyCode());
    }

    /**
     * Add percentage calculations to accounts.
     */
    private function addPercentages(array $accounts, Money $total): array
    {
        foreach ($accounts as &$account) {
            $percentage = $total->isZero()
                ? 0
                : round(($account['balance'] / $total->toDecimal()) * 100, 2);

            $account['percentage'] = $percentage;
            $account['percentage_formatted'] = $percentage . '%';

            if (! empty($account['children'])) {
                $account['children'] = $this->addPercentages($account['children'], $total);
            }
        }

        return $accounts;
    }

    /**
     * Sum all account balances in a section.
     */
    private function sumSection(array $accounts): Money
    {
        $total = Money::zero($this->getCurrencyCode());

        foreach ($accounts as $account) {
            $total = $total->add(
                Money::fromDecimal($account['balance'], $this->getCurrencyCode())
            );
        }

        return $total;
    }
}
