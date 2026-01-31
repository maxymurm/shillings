<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\Split;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class IncomeExpenseChart extends ChartWidget
{
    protected ?string $heading = 'Income vs Expenses';

    protected static ?int $sort = 2;

    public ?string $filter = null;

    protected function getFilters(): ?array
    {
        $currentYear = now()->year;
        return [
            (string) $currentYear => $currentYear,
            (string) ($currentYear - 1) => $currentYear - 1,
            (string) ($currentYear - 2) => $currentYear - 2,
        ];
    }

    protected function getData(): array
    {
        $year = $this->filter ?? now()->year;
        $companyId = session('current_company_id');

        // Get income accounts (type = Income/Revenue)
        $incomeAccountIds = Account::query()
            ->whereHas('accountType', fn ($q) => $q->where('name', 'Income'))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->pluck('id');

        // Get expense accounts (type = Expense)
        $expenseAccountIds = Account::query()
            ->whereHas('accountType', fn ($q) => $q->where('name', 'Expense'))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->pluck('id');

        $months = [];
        $incomeData = [];
        $expenseData = [];
        $netIncomeData = [];

        for ($month = 1; $month <= 12; $month++) {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $months[] = $startDate->format('M');

            // Calculate income (credits to income accounts)
            $income = Split::query()
                ->whereIn('account_id', $incomeAccountIds)
                ->where('action', Split::CREDIT)
                ->whereHas('transaction', fn ($q) => $q
                    ->where('is_posted', true)
                    ->where('is_void', false)
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                )
                ->sum(DB::raw('amount_num / amount_denom'));

            // Calculate expenses (debits to expense accounts)
            $expense = Split::query()
                ->whereIn('account_id', $expenseAccountIds)
                ->where('action', Split::DEBIT)
                ->whereHas('transaction', fn ($q) => $q
                    ->where('is_posted', true)
                    ->where('is_void', false)
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                )
                ->sum(DB::raw('amount_num / amount_denom'));

            $incomeData[] = round($income, 2);
            $expenseData[] = round($expense, 2);
            $netIncomeData[] = round($income - $expense, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Income',
                    'data' => $incomeData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Expenses',
                    'data' => $expenseData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.5)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Net Income',
                    'data' => $netIncomeData,
                    'type' => 'line',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderWidth' => 3,
                    'fill' => false,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { return '$' + value.toLocaleString(); }",
                    ],
                ],
            ],
        ];
    }
}
