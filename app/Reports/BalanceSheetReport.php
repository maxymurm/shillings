<?php

namespace App\Reports;

use App\Models\Account;
use App\Models\Split;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;

/**
 * Balance Sheet Report (Statement of Financial Position)
 *
 * Shows Assets = Liabilities + Equity at a point in time.
 */
class BalanceSheetReport extends BaseReport
{
    private ?array $comparisonData = null;
    private ?string $comparisonDate = null;

    public function getName(): string
    {
        return 'Balance Sheet';
    }

    public function getDescription(): string
    {
        return 'Statement of Financial Position showing Assets, Liabilities, and Equity.';
    }

    /**
     * Enable comparison with a previous date.
     */
    public function withComparison(string $date): self
    {
        $this->comparisonDate = $date;

        return $this;
    }

    /**
     * Generate the balance sheet report.
     */
    public function generate(): array
    {
        $company = $this->getCompany();
        $asOfDate = $this->getEndDate();

        // Get hierarchical accounts grouped by type
        $assets = $this->getAccountsWithBalances('Asset', $asOfDate);
        $liabilities = $this->getAccountsWithBalances('Liability', $asOfDate);
        $equity = $this->getAccountsWithBalances('Equity', $asOfDate);

        // Calculate retained earnings (Income - Expenses)
        $retainedEarnings = $this->calculateRetainedEarnings($asOfDate);

        // Calculate totals
        $totalAssets = $this->sumSection($assets);
        $totalLiabilities = $this->sumSection($liabilities);
        $totalEquity = $this->sumSection($equity)->add($retainedEarnings);
        $totalLiabilitiesAndEquity = $totalLiabilities->add($totalEquity);

        // Validate accounting equation
        $difference = $totalAssets->subtract($totalLiabilitiesAndEquity);
        $isBalanced = $difference->isZero();

        if (! $isBalanced) {
            $this->errors[] = "Balance sheet is out of balance by {$difference->toDecimal()}";
        }

        $this->data = [
            'assets' => [
                'label' => 'Assets',
                'accounts' => $assets,
                'total' => $totalAssets->toDecimal(),
                'total_formatted' => $this->formatMoney($totalAssets->toDecimal()),
            ],
            'liabilities' => [
                'label' => 'Liabilities',
                'accounts' => $liabilities,
                'total' => $totalLiabilities->toDecimal(),
                'total_formatted' => $this->formatMoney($totalLiabilities->toDecimal()),
            ],
            'equity' => [
                'label' => 'Equity',
                'accounts' => $equity,
                'retained_earnings' => [
                    'name' => 'Retained Earnings',
                    'balance' => $retainedEarnings->toDecimal(),
                    'balance_formatted' => $this->formatMoney($retainedEarnings->toDecimal()),
                ],
                'total' => $totalEquity->toDecimal(),
                'total_formatted' => $this->formatMoney($totalEquity->toDecimal()),
            ],
            'totals' => [
                'assets' => $totalAssets->toDecimal(),
                'liabilities_and_equity' => $totalLiabilitiesAndEquity->toDecimal(),
                'difference' => $difference->toDecimal(),
                'is_balanced' => $isBalanced,
            ],
            'as_of_date' => $asOfDate->toDateString(),
        ];

        $this->generated = true;

        return $this->data;
    }

    /**
     * Get accounts with balances for a type.
     */
    private function getAccountsWithBalances(string $typeName, $asOfDate): array
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

        return $this->buildAccountTree($accounts, $asOfDate);
    }

    /**
     * Build hierarchical account tree with balances.
     */
    private function buildAccountTree($accounts, $asOfDate, int $depth = 0): array
    {
        $result = [];

        foreach ($accounts as $account) {
            $children = [];

            if ($account->children->isNotEmpty()) {
                $children = $this->buildAccountTree($account->children, $asOfDate, $depth + 1);
            }

            // Calculate balance (including children)
            $balance = $this->getAccountBalance($account, $asOfDate);
            $childrenTotal = Money::zero($this->getCurrencyCode());

            foreach ($children as $child) {
                $childrenTotal = $childrenTotal->add(
                    Money::fromDecimal($child['balance'], $this->getCurrencyCode())
                );
            }

            $totalBalance = $account->is_placeholder
                ? $childrenTotal
                : $balance->add($childrenTotal);

            // Skip zero-balance branches for cleaner reports
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
     * Get account balance as of a date.
     */
    private function getAccountBalance(Account $account, $asOfDate): Money
    {
        if ($account->is_placeholder) {
            return Money::zero($this->getCurrencyCode());
        }

        $result = DB::table('splits')
            ->join('transactions', 'splits.transaction_id', '=', 'transactions.id')
            ->where('splits.account_id', $account->id)
            ->where('transactions.is_void', false)
            ->whereNotNull('transactions.posted_at')
            ->where(function ($query) use ($asOfDate) {
                $query->whereDate('transactions.post_date', '<=', $asOfDate)
                    ->orWhere(function ($q) use ($asOfDate) {
                        $q->whereNull('transactions.post_date')
                            ->whereDate('transactions.date', '<=', $asOfDate);
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

        return Money::fromFraction((int) ($result->net_num ?? 0), (int) $result->denom, $this->getCurrencyCode());
    }

    /**
     * Calculate retained earnings (cumulative Income - Expenses).
     */
    private function calculateRetainedEarnings($asOfDate): Money
    {
        $company = $this->getCompany();

        // Get income and expense totals
        $result = DB::table('splits')
            ->join('transactions', 'splits.transaction_id', '=', 'transactions.id')
            ->join('accounts', 'splits.account_id', '=', 'accounts.id')
            ->join('account_types', 'accounts.account_type_id', '=', 'account_types.id')
            ->where('accounts.company_id', $company->id)
            ->where('transactions.is_void', false)
            ->whereNotNull('transactions.posted_at')
            ->whereIn('account_types.name', ['Income', 'Expense'])
            ->where(function ($query) use ($asOfDate) {
                $query->whereDate('transactions.post_date', '<=', $asOfDate)
                    ->orWhere(function ($q) use ($asOfDate) {
                        $q->whereNull('transactions.post_date')
                            ->whereDate('transactions.date', '<=', $asOfDate);
                    });
            })
            ->selectRaw('
                account_types.name as type_name,
                SUM(CASE WHEN splits.action = ? THEN splits.amount_num ELSE -splits.amount_num END) as net_num,
                MAX(splits.amount_denom) as denom
            ', [Split::DEBIT])
            ->groupBy('account_types.name')
            ->get()
            ->keyBy('type_name');

        $income = Money::zero($this->getCurrencyCode());
        $expenses = Money::zero($this->getCurrencyCode());

        if (isset($result['Income']) && $result['Income']->denom) {
            // Income has credit-normal balance, so negate to get positive income
            $income = Money::fromFraction(
                (int) -($result['Income']->net_num ?? 0),
                (int) $result['Income']->denom,
                $this->getCurrencyCode()
            );
        }

        if (isset($result['Expense']) && $result['Expense']->denom) {
            $expenses = Money::fromFraction(
                (int) ($result['Expense']->net_num ?? 0),
                (int) $result['Expense']->denom,
                $this->getCurrencyCode()
            );
        }

        // Retained Earnings = Income - Expenses
        return $income->subtract($expenses);
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
