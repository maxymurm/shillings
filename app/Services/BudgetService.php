<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetAccount;
use App\Models\Account;
use App\Models\Company;
use App\Models\Split;
use App\ValueObjects\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BudgetService
{
    protected AccountService $accountService;

    public function __construct(AccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    /**
     * Create a new budget with account allocations.
     */
    public function create(array $data, ?array $accounts = null): Budget
    {
        return DB::transaction(function () use ($data, $accounts) {
            $budget = Budget::create($data);

            if ($accounts) {
                foreach ($accounts as $accountData) {
                    $accountData['budget_id'] = $budget->id;
                    BudgetAccount::create($accountData);
                }
            }

            return $budget->fresh(['accounts']);
        });
    }

    /**
     * Update a budget with account allocations.
     */
    public function update(Budget $budget, array $data, ?array $accounts = null): Budget
    {
        return DB::transaction(function () use ($budget, $data, $accounts) {
            $budget->update($data);

            if ($accounts !== null) {
                $this->syncAccounts($budget, $accounts);
            }

            return $budget->fresh(['accounts']);
        });
    }

    /**
     * Sync budget accounts.
     */
    protected function syncAccounts(Budget $budget, array $accounts): void
    {
        $existingIds = collect($accounts)->pluck('id')->filter()->toArray();
        
        // Delete removed accounts
        $budget->budgetAccounts()->whereNotIn('id', $existingIds)->delete();

        foreach ($accounts as $accountData) {
            $accountData['budget_id'] = $budget->id;
            
            if (isset($accountData['id'])) {
                $budget->budgetAccounts()->where('id', $accountData['id'])->update($accountData);
            } else {
                BudgetAccount::create($accountData);
            }
        }
    }

    /**
     * Clone a budget for a new fiscal year.
     */
    public function cloneForNewYear(Budget $budget, int $newYear): Budget
    {
        return DB::transaction(function () use ($budget, $newYear) {
            $newBudget = $budget->replicate();
            $newBudget->name = preg_replace('/\d{4}/', $newYear, $budget->name) ?: "{$budget->name} - {$newYear}";
            $newBudget->fiscal_year = $newYear;
            $newBudget->is_active = true;
            
            // Adjust dates
            $yearDiff = $newYear - $budget->fiscal_year;
            $newBudget->start_date = $budget->start_date->addYears($yearDiff);
            $newBudget->end_date = $budget->end_date->addYears($yearDiff);
            
            $newBudget->save();

            // Clone all budget accounts
            foreach ($budget->budgetAccounts as $budgetAccount) {
                $newBudgetAccount = $budgetAccount->replicate();
                $newBudgetAccount->budget_id = $newBudget->id;
                $newBudgetAccount->save();
            }

            // Deactivate old budget
            $budget->update(['is_active' => false]);

            return $newBudget->fresh(['budgetAccounts']);
        });
    }

    /**
     * Get budget vs actual report.
     */
    public function getBudgetVsActual(Budget $budget, ?int $period = null): array
    {
        $currency = $budget->company->currency_code ?? 'KES';
        $periods = $period ? [$period] : range(1, $budget->num_periods);
        
        $periodReports = [];
        $totals = [
            'budgeted' => Money::zero($currency),
            'actual' => Money::zero($currency),
            'variance' => Money::zero($currency),
        ];

        foreach ($periods as $p) {
            // Get all budget entries for this period
            $budgetEntries = $budget->budgetAccounts()
                ->where('period_num', $p)
                ->get();

            $periodBudgeted = Money::zero($currency);
            foreach ($budgetEntries as $entry) {
                $periodBudgeted = $periodBudgeted->add(
                    Money::fromFraction($entry->amount_num ?? 0, $entry->amount_denom ?? 100, $currency)
                );
            }

            // Get actual spending for this period
            $periodActual = $this->getActualForPeriod($budget, $p);

            $variance = $periodBudgeted->subtract($periodActual);
            $variancePercent = $periodBudgeted->isZero() ? 0 : 
                ($variance->toDecimal() / $periodBudgeted->toDecimal()) * 100;

            $periodReports[] = [
                'period' => $p,
                'budgeted_num' => $periodBudgeted->getNumerator(),
                'budgeted_denom' => $periodBudgeted->getDenominator(),
                'actual_num' => $periodActual->getNumerator(),
                'actual_denom' => $periodActual->getDenominator(),
                'variance_num' => $variance->getNumerator(),
                'variance_denom' => $variance->getDenominator(),
                'variance_percent' => round($variancePercent, 2),
            ];

            $totals['budgeted'] = $totals['budgeted']->add($periodBudgeted);
            $totals['actual'] = $totals['actual']->add($periodActual);
        }

        $totals['variance'] = $totals['budgeted']->subtract($totals['actual']);
        $totals['variance_percent'] = $totals['budgeted']->isZero() ? 0 :
            ($totals['variance']->toDecimal() / $totals['budgeted']->toDecimal()) * 100;

        return [
            'budget' => $budget,
            'periods' => $periodReports,
            'totals' => [
                'budgeted_num' => $totals['budgeted']->getNumerator(),
                'budgeted_denom' => $totals['budgeted']->getDenominator(),
                'actual_num' => $totals['actual']->getNumerator(),
                'actual_denom' => $totals['actual']->getDenominator(),
                'variance_num' => $totals['variance']->getNumerator(),
                'variance_denom' => $totals['variance']->getDenominator(),
                'variance_percent' => round($totals['variance_percent'], 2),
            ],
        ];
    }

    /**
     * Get actual spending for a specific period.
     */
    protected function getActualForPeriod(Budget $budget, int $period): Money
    {
        $currency = $budget->company->currency_code ?? 'KES';
        $dates = $budget->getPeriodDates($period);

        $total = Money::zero($currency);
        
        $accountIds = $budget->budgetAccounts()
            ->where('period_num', $period)
            ->pluck('account_id')
            ->unique();

        foreach ($accountIds as $accountId) {
            $account = Account::find($accountId);
            if ($account) {
                $balance = $this->accountService->getBalanceForPeriod(
                    $account,
                    $dates['start'],
                    $dates['end']
                );
                $balanceMoney = Money::fromDecimal($balance, $currency);
                $total = $total->add($balanceMoney);
            }
        }

        return $total;
    }

    /**
     * Get actual amounts for an account within budget periods.
     */
    protected function getActualForPeriods(Account $account, Budget $budget, array $periods): Money
    {
        $currency = $budget->company->currency_code ?? 'KES';
        $total = Money::zero($currency);

        foreach ($periods as $period) {
            $dates = $budget->getPeriodDates($period);
            $balance = $this->accountService->getBalanceForPeriod(
                $account,
                $dates['start'],
                $dates['end']
            );
            // Convert float to Money before adding
            $balanceMoney = Money::fromDecimal($balance, $currency);
            $total = $total->add($balanceMoney);
        }

        return $total;
    }

    /**
     * Get budget utilization summary.
     */
    public function getUtilizationSummary(Budget $budget): array
    {
        $currency = $budget->company->currency_code ?? 'KES';
        $currentPeriod = $budget->getCurrentPeriod();
        
        $totalBudget = Money::zero($currency);
        $totalActual = Money::zero($currency);
        $accountsReport = [];
        
        // Group budget accounts by account_id
        $accountIds = $budget->budgetAccounts()->distinct()->pluck('account_id');
        
        foreach ($accountIds as $accountId) {
            $account = Account::find($accountId);
            if (!$account) continue;

            // Get budgeted amount for this account up to current period
            $budgetEntries = $budget->budgetAccounts()
                ->where('account_id', $accountId)
                ->whereIn('period_num', range(1, max(1, $currentPeriod)))
                ->get();

            $accountBudget = Money::zero($currency);
            foreach ($budgetEntries as $entry) {
                $accountBudget = $accountBudget->add(
                    Money::fromFraction($entry->amount_num ?? 0, $entry->amount_denom ?? 100, $currency)
                );
            }

            // Get actual for this account
            $accountActual = $this->getActualForPeriods(
                $account, 
                $budget, 
                range(1, max(1, $currentPeriod))
            );

            $utilizationPercent = $accountBudget->isZero() ? 0 :
                ($accountActual->toDecimal() / $accountBudget->toDecimal()) * 100;

            $accountsReport[] = [
                'account_id' => $accountId,
                'account_name' => $account->name,
                'budgeted_num' => $accountBudget->getNumerator(),
                'budgeted_denom' => $accountBudget->getDenominator(),
                'actual_num' => $accountActual->getNumerator(),
                'actual_denom' => $accountActual->getDenominator(),
                'utilization_percent' => round($utilizationPercent, 2),
            ];

            $totalBudget = $totalBudget->add($accountBudget);
            $totalActual = $totalActual->add($accountActual);
        }

        $overallUtilization = $totalBudget->isZero() ? 0 :
            ($totalActual->toDecimal() / $totalBudget->toDecimal()) * 100;

        return [
            'accounts' => $accountsReport,
            'overall_utilization' => round($overallUtilization, 2),
            'total_budget' => $totalBudget,
            'total_actual' => $totalActual,
        ];
    }

    /**
     * Distribute annual amount across periods.
     */
    public function distributeAmount(Budget $budget, string $accountId, Money $annualAmount, string $method = 'equal'): void
    {
        DB::transaction(function () use ($budget, $accountId, $annualAmount, $method) {
            // Remove existing entries for this account
            $budget->budgetAccounts()->where('account_id', $accountId)->delete();

            $amounts = $this->calculateDistribution($annualAmount, $budget->num_periods, $method);

            foreach ($amounts as $period => $amount) {
                BudgetAccount::create([
                    'budget_id' => $budget->id,
                    'account_id' => $accountId,
                    'period_num' => $period + 1,
                    'amount_num' => $amount->getNumerator(),
                    'amount_denom' => $amount->getDenominator(),
                ]);
            }
        });
    }

    /**
     * Calculate distribution of amount across periods.
     */
    protected function calculateDistribution(Money $total, int $periods, string $method): array
    {
        $amounts = [];
        $currency = $total->getCurrencyCode();

        switch ($method) {
            case 'equal':
                $perPeriod = $total->divide($periods);
                for ($i = 0; $i < $periods; $i++) {
                    $amounts[$i] = $perPeriod;
                }
                break;

            case 'front_loaded':
                // 60% in first half, 40% in second half
                $firstHalf = (int) ceil($periods / 2);
                $firstHalfTotal = $total->multiply(0.6);
                $secondHalfTotal = $total->subtract($firstHalfTotal);
                
                $perPeriodFirst = $firstHalfTotal->divide($firstHalf);
                $perPeriodSecond = $secondHalfTotal->divide($periods - $firstHalf);
                
                for ($i = 0; $i < $periods; $i++) {
                    $amounts[$i] = $i < $firstHalf ? $perPeriodFirst : $perPeriodSecond;
                }
                break;

            case 'back_loaded':
                // 40% in first half, 60% in second half
                $firstHalf = (int) ceil($periods / 2);
                $firstHalfTotal = $total->multiply(0.4);
                $secondHalfTotal = $total->subtract($firstHalfTotal);
                
                $perPeriodFirst = $firstHalfTotal->divide($firstHalf);
                $perPeriodSecond = $secondHalfTotal->divide($periods - $firstHalf);
                
                for ($i = 0; $i < $periods; $i++) {
                    $amounts[$i] = $i < $firstHalf ? $perPeriodFirst : $perPeriodSecond;
                }
                break;

            default:
                $perPeriod = $total->divide($periods);
                for ($i = 0; $i < $periods; $i++) {
                    $amounts[$i] = $perPeriod;
                }
        }

        return $amounts;
    }

    /**
     * Get active budgets for a company.
     */
    public function getActiveBudgets(Company $company): Collection
    {
        return Budget::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('fiscal_year', 'desc')
            ->get();
    }

    /**
     * Get budget forecast (projected vs budget).
     */
    public function getForecast(Budget $budget): array
    {
        $currency = $budget->company->currency_code ?? 'KES';
        $currentPeriod = $budget->getCurrentPeriod();
        $summary = $this->getUtilizationSummary($budget);
        
        // Project full year based on current run rate
        $runRate = $currentPeriod > 0 
            ? $summary['total_actual']->divide($currentPeriod)->multiply($budget->num_periods)
            : Money::zero($currency);

        $remaining = $summary['total_budget']->subtract($summary['total_actual']);

        // Calculate forecast by period
        $forecastByPeriod = [];
        for ($i = 1; $i <= $budget->num_periods; $i++) {
            $forecastByPeriod[$i] = [
                'period' => $i,
                'projected_num' => 0,
                'projected_denom' => 100,
            ];
        }

        return [
            'projected_total_num' => $runRate->getNumerator(),
            'projected_total_denom' => $runRate->getDenominator(),
            'remaining_budget_num' => $remaining->getNumerator(),
            'remaining_budget_denom' => $remaining->getDenominator(),
            'run_rate' => $currentPeriod > 0 ? round($summary['total_actual']->toDecimal() / $currentPeriod, 2) : 0,
            'forecast_by_period' => $forecastByPeriod,
        ];
    }
}
