<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\Company;
use App\Services\AccountService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $companyId = session('active_company_id');

        if (! $companyId) {
            return [
                Stat::make('Cash Balance', '-')
                    ->description('No company selected')
                    ->color('gray'),
            ];
        }

        $company = Company::find($companyId);
        $currencyCode = $company?->defaultCurrency?->code ?? 'USD';
        $accountService = app(AccountService::class);

        // Calculate cash balance (Asset accounts with code starting with 1)
        $cashBalance = $this->getCashBalance($companyId, $accountService);

        // Calculate net worth (Assets - Liabilities)
        $netWorth = $this->getNetWorth($companyId, $accountService);

        // Calculate income and expenses (current month)
        [$income, $expenses] = $this->getCurrentMonthIncomeExpenses($companyId, $accountService);

        $netIncome = $income - $expenses;

        return [
            Stat::make('Cash Balance', number_format($cashBalance, 2) . ' ' . $currencyCode)
                ->description('Available cash')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color($cashBalance >= 0 ? 'success' : 'danger'),

            Stat::make('Net Worth', number_format($netWorth, 2) . ' ' . $currencyCode)
                ->description('Assets - Liabilities')
                ->descriptionIcon('heroicon-o-scale')
                ->color($netWorth >= 0 ? 'success' : 'danger'),

            Stat::make('Income (This Month)', number_format($income, 2) . ' ' . $currencyCode)
                ->description('Total revenue')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('success'),

            Stat::make('Expenses (This Month)', number_format($expenses, 2) . ' ' . $currencyCode)
                ->description('Total expenses')
                ->descriptionIcon('heroicon-o-arrow-trending-down')
                ->color('danger'),

            Stat::make('Net Income', number_format($netIncome, 2) . ' ' . $currencyCode)
                ->description('Income - Expenses')
                ->descriptionIcon('heroicon-o-calculator')
                ->color($netIncome >= 0 ? 'success' : 'danger'),
        ];
    }

    private function getCashBalance(string $companyId, AccountService $accountService): float
    {
        $cashAccounts = Account::query()
            ->where('company_id', $companyId)
            ->where('is_placeholder', false)
            ->whereHas('accountType', fn ($q) => $q->where('name', 'Asset'))
            ->where(function ($q) {
                $q->where('code', 'like', '111%')
                    ->orWhere('code', 'like', '112%')
                    ->orWhere('name', 'like', '%cash%')
                    ->orWhere('name', 'like', '%bank%')
                    ->orWhere('name', 'like', '%checking%');
            })
            ->get();

        $total = 0;
        foreach ($cashAccounts as $account) {
            $total += $accountService->getBalance($account);
        }

        return $total;
    }

    private function getNetWorth(string $companyId, AccountService $accountService): float
    {
        $assets = Account::query()
            ->where('company_id', $companyId)
            ->where('is_placeholder', false)
            ->whereHas('accountType', fn ($q) => $q->where('name', 'Asset'))
            ->get();

        $liabilities = Account::query()
            ->where('company_id', $companyId)
            ->where('is_placeholder', false)
            ->whereHas('accountType', fn ($q) => $q->where('name', 'Liability'))
            ->get();

        $totalAssets = 0;
        foreach ($assets as $account) {
            $totalAssets += $accountService->getBalance($account);
        }

        $totalLiabilities = 0;
        foreach ($liabilities as $account) {
            $totalLiabilities += abs($accountService->getBalance($account));
        }

        return $totalAssets - $totalLiabilities;
    }

    private function getCurrentMonthIncomeExpenses(string $companyId, AccountService $accountService): array
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        // Income accounts
        $incomeAccounts = Account::query()
            ->where('company_id', $companyId)
            ->where('is_placeholder', false)
            ->whereHas('accountType', fn ($q) => $q->where('name', 'Income'))
            ->get();

        // Expense accounts
        $expenseAccounts = Account::query()
            ->where('company_id', $companyId)
            ->where('is_placeholder', false)
            ->whereHas('accountType', fn ($q) => $q->where('name', 'Expense'))
            ->get();

        $income = 0;
        foreach ($incomeAccounts as $account) {
            $income += abs($accountService->getBalanceForPeriod($account, $startOfMonth, $endOfMonth));
        }

        $expenses = 0;
        foreach ($expenseAccounts as $account) {
            $expenses += abs($accountService->getBalanceForPeriod($account, $startOfMonth, $endOfMonth));
        }

        return [$income, $expenses];
    }
}
