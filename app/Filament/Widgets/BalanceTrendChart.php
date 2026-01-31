<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\Split;
use App\Services\AccountService;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class BalanceTrendChart extends ChartWidget
{
    protected ?string $heading = 'Account Balance Trend';

    protected static ?int $sort = 4;

    public ?string $filter = 'last_6_months';

    protected function getFilters(): ?array
    {
        return [
            'last_30_days' => 'Last 30 Days',
            'last_3_months' => 'Last 3 Months',
            'last_6_months' => 'Last 6 Months',
            'this_year' => 'This Year',
            'last_year' => 'Last Year',
        ];
    }

    protected function getData(): array
    {
        $companyId = session('current_company_id');

        // Get date range and granularity
        [$startDate, $endDate, $granularity] = $this->getDateRangeAndGranularity();

        // Get main asset accounts (Cash, Bank)
        $accounts = Account::query()
            ->whereHas('accountType', fn ($q) => $q->where('name', 'Asset'))
            ->whereNull('parent_id')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->take(5)
            ->get();

        $labels = [];
        $datasets = [];

        // Generate date labels
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $labels[] = match ($granularity) {
                'daily' => $current->format('M d'),
                'weekly' => $current->format('M d'),
                'monthly' => $current->format('M Y'),
            };
            $current = match ($granularity) {
                'daily' => $current->addDay(),
                'weekly' => $current->addWeek(),
                'monthly' => $current->addMonth(),
            };
        }

        $colors = [
            ['rgba(59, 130, 246, 1)', 'rgba(59, 130, 246, 0.1)'],   // Blue
            ['rgba(34, 197, 94, 1)', 'rgba(34, 197, 94, 0.1)'],     // Green
            ['rgba(249, 115, 22, 1)', 'rgba(249, 115, 22, 0.1)'],   // Orange
            ['rgba(139, 92, 246, 1)', 'rgba(139, 92, 246, 0.1)'],   // Purple
            ['rgba(236, 72, 153, 1)', 'rgba(236, 72, 153, 0.1)'],   // Pink
        ];

        foreach ($accounts as $index => $account) {
            $data = [];
            $current = $startDate->copy();
            
            // Get all child account IDs
            $accountIds = collect([$account->id]);
            $this->collectChildAccountIds($account, $accountIds);
            
            while ($current->lte($endDate)) {
                $periodEnd = match ($granularity) {
                    'daily' => $current->copy()->endOfDay(),
                    'weekly' => $current->copy()->endOfWeek(),
                    'monthly' => $current->copy()->endOfMonth(),
                };

                // Calculate balance up to this period
                $debits = Split::query()
                    ->whereIn('account_id', $accountIds)
                    ->where('action', Split::DEBIT)
                    ->whereHas('transaction', fn ($q) => $q
                        ->where('is_posted', true)
                        ->where('is_void', false)
                        ->where('transaction_date', '<=', $periodEnd)
                    )
                    ->sum(DB::raw('amount_num / amount_denom'));

                $credits = Split::query()
                    ->whereIn('account_id', $accountIds)
                    ->where('action', Split::CREDIT)
                    ->whereHas('transaction', fn ($q) => $q
                        ->where('is_posted', true)
                        ->where('is_void', false)
                        ->where('transaction_date', '<=', $periodEnd)
                    )
                    ->sum(DB::raw('amount_num / amount_denom'));

                $data[] = round($debits - $credits, 2);

                $current = match ($granularity) {
                    'daily' => $current->addDay(),
                    'weekly' => $current->addWeek(),
                    'monthly' => $current->addMonth(),
                };
            }

            $color = $colors[$index % count($colors)];
            $datasets[] = [
                'label' => $account->name,
                'data' => $data,
                'borderColor' => $color[0],
                'backgroundColor' => $color[1],
                'borderWidth' => 2,
                'fill' => true,
                'tension' => 0.3,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function collectChildAccountIds(Account $account, &$ids): void
    {
        foreach ($account->children as $child) {
            $ids->push($child->id);
            $this->collectChildAccountIds($child, $ids);
        }
    }

    protected function getDateRangeAndGranularity(): array
    {
        return match ($this->filter) {
            'last_30_days' => [
                Carbon::now()->subDays(30),
                Carbon::now(),
                'daily',
            ],
            'last_3_months' => [
                Carbon::now()->subMonths(3)->startOfMonth(),
                Carbon::now()->endOfMonth(),
                'weekly',
            ],
            'this_year' => [
                Carbon::now()->startOfYear(),
                Carbon::now()->endOfYear(),
                'monthly',
            ],
            'last_year' => [
                Carbon::now()->subYear()->startOfYear(),
                Carbon::now()->subYear()->endOfYear(),
                'monthly',
            ],
            default => [
                Carbon::now()->subMonths(6)->startOfMonth(),
                Carbon::now()->endOfMonth(),
                'monthly',
            ],
        };
    }

    protected function getType(): string
    {
        return 'line';
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
                    'beginAtZero' => false,
                    'ticks' => [
                        'callback' => "function(value) { return '$' + value.toLocaleString(); }",
                    ],
                ],
            ],
        ];
    }
}
