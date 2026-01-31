<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\Split;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ExpenseBreakdownChart extends ChartWidget
{
    protected ?string $heading = 'Expense Breakdown';

    protected static ?int $sort = 3;

    public ?string $filter = 'this_month';

    protected function getFilters(): ?array
    {
        return [
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_quarter' => 'This Quarter',
            'this_year' => 'This Year',
        ];
    }

    protected function getData(): array
    {
        $companyId = session('current_company_id');

        // Get date range based on filter
        [$startDate, $endDate] = $this->getDateRange();

        // Get top-level expense accounts
        $expenseAccounts = Account::query()
            ->whereHas('accountType', fn ($q) => $q->where('name', 'Expense'))
            ->whereNull('parent_id')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get();

        $labels = [];
        $data = [];
        $colors = [
            'rgba(239, 68, 68, 0.8)',   // Red
            'rgba(249, 115, 22, 0.8)',  // Orange
            'rgba(245, 158, 11, 0.8)',  // Amber
            'rgba(234, 179, 8, 0.8)',   // Yellow
            'rgba(132, 204, 22, 0.8)',  // Lime
            'rgba(34, 197, 94, 0.8)',   // Green
            'rgba(20, 184, 166, 0.8)',  // Teal
            'rgba(6, 182, 212, 0.8)',   // Cyan
            'rgba(59, 130, 246, 0.8)',  // Blue
            'rgba(139, 92, 246, 0.8)',  // Violet
            'rgba(168, 85, 247, 0.8)',  // Purple
            'rgba(236, 72, 153, 0.8)',  // Pink
        ];

        foreach ($expenseAccounts as $index => $account) {
            // Get account and all children
            $accountIds = collect([$account->id]);
            $this->collectChildAccountIds($account, $accountIds);

            // Calculate total for this category
            $total = Split::query()
                ->whereIn('account_id', $accountIds)
                ->where('action', Split::DEBIT)
                ->whereHas('transaction', fn ($q) => $q
                    ->where('is_posted', true)
                    ->where('is_void', false)
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                )
                ->sum(DB::raw('amount_num / amount_denom'));

            if ($total > 0) {
                $labels[] = $account->name;
                $data[] = round($total, 2);
            }
        }

        // Sort by amount descending
        $combined = array_map(null, $labels, $data);
        usort($combined, fn ($a, $b) => $b[1] <=> $a[1]);
        
        $labels = array_column($combined, 0);
        $data = array_column($combined, 1);

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderWidth' => 1,
                ],
            ],
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

    protected function getDateRange(): array
    {
        return match ($this->filter) {
            'last_month' => [
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth(),
            ],
            'this_quarter' => [
                Carbon::now()->startOfQuarter(),
                Carbon::now()->endOfQuarter(),
            ],
            'this_year' => [
                Carbon::now()->startOfYear(),
                Carbon::now()->endOfYear(),
            ],
            default => [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ],
        };
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
                    'display' => true,
                    'position' => 'right',
                ],
            ],
            'cutout' => '60%',
        ];
    }
}
