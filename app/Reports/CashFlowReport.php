<?php

namespace App\Reports;

use App\Models\Account;
use App\Models\Split;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;

/**
 * Cash Flow Statement Report
 *
 * Shows cash flows from Operating, Investing, and Financing activities.
 * Uses the indirect method starting with Net Income.
 */
class CashFlowReport extends BaseReport
{
    /**
     * Account codes/patterns classified as investing activities.
     */
    private array $investingPatterns = ['12', '13']; // Fixed assets typically

    /**
     * Account codes/patterns classified as financing activities.
     */
    private array $financingPatterns = ['21', '22', '31', '32']; // Long-term liabilities, equity

    public function getName(): string
    {
        return 'Statement of Cash Flows';
    }

    public function getDescription(): string
    {
        return 'Cash flows from Operating, Investing, and Financing activities using indirect method.';
    }

    /**
     * Generate the cash flow statement.
     */
    public function generate(): array
    {
        $company = $this->getCompany();
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        // Calculate Net Income (starting point for indirect method)
        $netIncome = $this->calculateNetIncome($startDate, $endDate);

        // Get operating activities adjustments
        $operatingAdjustments = $this->getOperatingAdjustments($startDate, $endDate);
        $netOperating = $netIncome;
        foreach ($operatingAdjustments as $adj) {
            $netOperating = $netOperating->add(
                Money::fromDecimal($adj['amount'], $this->getCurrencyCode())
            );
        }

        // Get investing activities
        $investingActivities = $this->getInvestingActivities($startDate, $endDate);
        $netInvesting = Money::zero($this->getCurrencyCode());
        foreach ($investingActivities as $activity) {
            $netInvesting = $netInvesting->add(
                Money::fromDecimal($activity['amount'], $this->getCurrencyCode())
            );
        }

        // Get financing activities
        $financingActivities = $this->getFinancingActivities($startDate, $endDate);
        $netFinancing = Money::zero($this->getCurrencyCode());
        foreach ($financingActivities as $activity) {
            $netFinancing = $netFinancing->add(
                Money::fromDecimal($activity['amount'], $this->getCurrencyCode())
            );
        }

        // Calculate net change in cash
        $netChange = $netOperating->add($netInvesting)->add($netFinancing);

        // Get beginning and ending cash
        $beginningCash = $this->getCashBalance($startDate->copy()->subDay());
        $endingCash = $this->getCashBalance($endDate);

        // Verify reconciliation
        $expectedEnding = $beginningCash->add($netChange);
        $isReconciled = $expectedEnding->subtract($endingCash)->abs()->toDecimal() < 0.01;

        if (! $isReconciled) {
            $this->errors[] = 'Cash flow does not reconcile to ending cash balance';
        }

        $this->data = [
            'operating_activities' => [
                'label' => 'Cash Flows from Operating Activities',
                'net_income' => [
                    'label' => 'Net Income',
                    'amount' => $netIncome->toDecimal(),
                    'amount_formatted' => $this->formatMoney($netIncome->toDecimal()),
                ],
                'adjustments' => $operatingAdjustments,
                'net_cash' => $netOperating->toDecimal(),
                'net_cash_formatted' => $this->formatMoney($netOperating->toDecimal()),
            ],
            'investing_activities' => [
                'label' => 'Cash Flows from Investing Activities',
                'items' => $investingActivities,
                'net_cash' => $netInvesting->toDecimal(),
                'net_cash_formatted' => $this->formatMoney($netInvesting->toDecimal()),
            ],
            'financing_activities' => [
                'label' => 'Cash Flows from Financing Activities',
                'items' => $financingActivities,
                'net_cash' => $netFinancing->toDecimal(),
                'net_cash_formatted' => $this->formatMoney($netFinancing->toDecimal()),
            ],
            'summary' => [
                'net_change_in_cash' => $netChange->toDecimal(),
                'net_change_formatted' => $this->formatMoney($netChange->toDecimal()),
                'beginning_cash' => $beginningCash->toDecimal(),
                'beginning_cash_formatted' => $this->formatMoney($beginningCash->toDecimal()),
                'ending_cash' => $endingCash->toDecimal(),
                'ending_cash_formatted' => $this->formatMoney($endingCash->toDecimal()),
                'is_reconciled' => $isReconciled,
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
     * Calculate net income for the period.
     */
    private function calculateNetIncome($startDate, $endDate): Money
    {
        $company = $this->getCompany();

        $result = DB::table('splits')
            ->join('transactions', 'splits.transaction_id', '=', 'transactions.id')
            ->join('accounts', 'splits.account_id', '=', 'accounts.id')
            ->join('account_types', 'accounts.account_type_id', '=', 'account_types.id')
            ->where('accounts.company_id', $company->id)
            ->where('transactions.is_void', false)
            ->whereNotNull('transactions.posted_at')
            ->whereIn('account_types.name', ['Income', 'Expense'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('transactions.post_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->whereNull('transactions.post_date')
                            ->whereBetween('transactions.date', [$startDate, $endDate]);
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

        return $income->subtract($expenses);
    }

    /**
     * Get operating activity adjustments (changes in working capital, depreciation, etc.).
     */
    private function getOperatingAdjustments($startDate, $endDate): array
    {
        $company = $this->getCompany();
        $adjustments = [];

        // Get changes in current assets and liabilities (excluding cash)
        $workingCapitalAccounts = Account::query()
            ->where('company_id', $company->id)
            ->whereHas('accountType', fn ($q) => $q->whereIn('name', ['Asset', 'Liability']))
            ->where('is_placeholder', false)
            ->where(function ($q) {
                $q->where('code', 'like', '11%') // Current assets
                    ->orWhere('code', 'like', '21%'); // Current liabilities
            })
            ->where('code', 'not like', '111%') // Exclude cash accounts
            ->orderBy('code')
            ->get();

        foreach ($workingCapitalAccounts as $account) {
            $beginningBalance = $this->getAccountBalanceAsOf($account, $startDate->copy()->subDay());
            $endingBalance = $this->getAccountBalanceAsOf($account, $endDate);
            $change = $endingBalance->subtract($beginningBalance);

            if ($change->isZero()) {
                continue;
            }

            // For assets: increase = cash outflow (negative adjustment)
            // For liabilities: increase = cash inflow (positive adjustment)
            $accountType = $account->accountType->name;
            $adjustedChange = $accountType === 'Asset' ? $change->negate() : $change;

            $adjustments[] = [
                'account_id' => $account->id,
                'name' => $this->getAdjustmentLabel($account, $accountType),
                'amount' => $adjustedChange->toDecimal(),
                'amount_formatted' => $this->formatMoney($adjustedChange->toDecimal()),
            ];
        }

        return $adjustments;
    }

    /**
     * Get investing activities.
     */
    private function getInvestingActivities($startDate, $endDate): array
    {
        return $this->getActivitiesForPatterns($this->investingPatterns, $startDate, $endDate);
    }

    /**
     * Get financing activities.
     */
    private function getFinancingActivities($startDate, $endDate): array
    {
        return $this->getActivitiesForPatterns($this->financingPatterns, $startDate, $endDate);
    }

    /**
     * Get activities for account code patterns.
     */
    private function getActivitiesForPatterns(array $patterns, $startDate, $endDate): array
    {
        $company = $this->getCompany();
        $activities = [];

        foreach ($patterns as $pattern) {
            $accounts = Account::query()
                ->where('company_id', $company->id)
                ->where('is_placeholder', false)
                ->where('code', 'like', $pattern . '%')
                ->get();

            foreach ($accounts as $account) {
                $beginningBalance = $this->getAccountBalanceAsOf($account, $startDate->copy()->subDay());
                $endingBalance = $this->getAccountBalanceAsOf($account, $endDate);
                $change = $endingBalance->subtract($beginningBalance);

                if ($change->isZero()) {
                    continue;
                }

                // For investing: asset increase = cash outflow (negative)
                // For financing: liability/equity increase = cash inflow (positive)
                $accountType = $account->accountType->name;
                $adjustedChange = $accountType === 'Asset' ? $change->negate() : $change;

                $activities[] = [
                    'account_id' => $account->id,
                    'name' => $account->name,
                    'amount' => $adjustedChange->toDecimal(),
                    'amount_formatted' => $this->formatMoney($adjustedChange->toDecimal()),
                ];
            }
        }

        return $activities;
    }

    /**
     * Get cash balance as of a date.
     */
    private function getCashBalance($asOfDate): Money
    {
        $company = $this->getCompany();

        // Cash accounts typically start with 111 or similar
        $cashAccounts = Account::query()
            ->where('company_id', $company->id)
            ->where('is_placeholder', false)
            ->where(function ($q) {
                $q->where('code', 'like', '111%')
                    ->orWhere('code', 'like', '112%')
                    ->orWhere('name', 'like', '%cash%')
                    ->orWhere('name', 'like', '%bank%');
            })
            ->get();

        $total = Money::zero($this->getCurrencyCode());

        foreach ($cashAccounts as $account) {
            $balance = $this->getAccountBalanceAsOf($account, $asOfDate);
            $total = $total->add($balance);
        }

        return $total;
    }

    /**
     * Get account balance as of a specific date.
     */
    private function getAccountBalanceAsOf(Account $account, $asOfDate): Money
    {
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
     * Get label for operating adjustment.
     */
    private function getAdjustmentLabel(Account $account, string $type): string
    {
        if ($type === 'Asset') {
            return "Change in {$account->name}";
        }

        return "Change in {$account->name}";
    }
}
