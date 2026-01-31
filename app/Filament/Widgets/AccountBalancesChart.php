<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Services\AccountService;
use Filament\Widgets\ChartWidget;

class AccountBalancesChart extends ChartWidget
{
    protected ?string $heading = 'Account Balances';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $companyId = session('active_company_id');

        if (! $companyId) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $accountService = app(AccountService::class);

        // Get top-level accounts by type
        $accountsByType = Account::query()
            ->where('company_id', $companyId)
            ->where('is_placeholder', true)
            ->whereNull('parent_id')
            ->with('accountType')
            ->get()
            ->groupBy(fn ($account) => $account->accountType?->name ?? 'Other');

        $labels = [];
        $data = [];
        $colors = [];

        $typeColors = [
            'Asset' => '#10b981',      // Green
            'Liability' => '#f59e0b',  // Amber
            'Equity' => '#3b82f6',     // Blue
            'Income' => '#8b5cf6',     // Purple
            'Expense' => '#ef4444',    // Red
        ];

        foreach ($accountsByType as $type => $accounts) {
            $total = 0;
            foreach ($accounts as $account) {
                $total += abs($accountService->getBalanceWithChildren($account));
            }

            if ($total > 0) {
                $labels[] = $type;
                $data[] = round($total, 2);
                $colors[] = $typeColors[$type] ?? '#6b7280';
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Balance',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
