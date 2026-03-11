<?php

namespace App\Queries;

use App\Models\Account;
use App\Models\Company;
use App\Models\Split;
use App\ValueObjects\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Account Register Query (Issue #32)
 *
 * Provides a register view for a single account showing all transactions
 * with a running balance, similar to a checkbook register.
 */
class AccountRegisterQuery
{
    protected Account $account;

    protected ?Carbon $startDate = null;

    protected ?Carbon $endDate = null;

    protected int $perPage = 50;

    protected ?string $search = null;

    protected bool $includeVoided = false;

    protected string $sortDirection = 'asc';

    /**
     * Create a new query instance.
     */
    public function __construct(Account $account)
    {
        $this->account = $account;
    }

    /**
     * Static factory method.
     */
    public static function for(Account $account): self
    {
        return new self($account);
    }

    /**
     * Filter by date range.
     */
    public function between(?Carbon $startDate, ?Carbon $endDate): self
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Set pagination size.
     */
    public function perPage(int $perPage): self
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * Search by description or memo.
     */
    public function search(?string $search): self
    {
        $this->search = $search;

        return $this;
    }

    /**
     * Include voided transactions.
     */
    public function includeVoided(bool $include = true): self
    {
        $this->includeVoided = $include;

        return $this;
    }

    /**
     * Set sort direction.
     */
    public function sortDirection(string $direction): self
    {
        $this->sortDirection = in_array($direction, ['asc', 'desc']) ? $direction : 'asc';

        return $this;
    }

    /**
     * Get paginated register entries.
     */
    public function paginate(?int $page = null)
    {
        $query = $this->buildQuery();

        $paginated = $query->paginate($this->perPage, ['*'], 'page', $page);

        // Calculate running balance for this page
        $items = $paginated->items();
        $runningBalance = $this->calculateStartingBalance($items);

        foreach ($items as $item) {
            $change = $item->action === Split::DEBIT
                ? Money::fromFraction($item->amount_num, $item->amount_denom, $this->getCurrencyCode())
                : Money::fromFraction($item->amount_num, $item->amount_denom, $this->getCurrencyCode())->negate();

            $runningBalance = $runningBalance->add($change);
            $item->running_balance = $runningBalance->toDecimal();
            $item->running_balance_formatted = $this->formatMoney($runningBalance->toDecimal());
            $item->amount_formatted = $this->formatAmount($item);
        }

        return $paginated;
    }

    /**
     * Get all register entries (without pagination).
     */
    public function get()
    {
        $items = $this->buildQuery()->get();

        $runningBalance = Money::zero($this->getCurrencyCode());

        foreach ($items as $item) {
            $change = $item->action === Split::DEBIT
                ? Money::fromFraction($item->amount_num, $item->amount_denom, $this->getCurrencyCode())
                : Money::fromFraction($item->amount_num, $item->amount_denom, $this->getCurrencyCode())->negate();

            $runningBalance = $runningBalance->add($change);
            $item->running_balance = $runningBalance->toDecimal();
            $item->running_balance_formatted = $this->formatMoney($runningBalance->toDecimal());
            $item->amount_formatted = $this->formatAmount($item);
        }

        return $items;
    }

    /**
     * Get the current balance (sum of all transactions).
     */
    public function getCurrentBalance(): Money
    {
        $result = DB::table('splits')
            ->join('transactions', 'splits.transaction_id', '=', 'transactions.id')
            ->where('splits.account_id', $this->account->id)
            ->where('transactions.is_void', false)
            ->where('transactions.is_posted', true)
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
     * Get account metadata for the register.
     */
    public function getMetadata(): array
    {
        $currentBalance = $this->getCurrentBalance();

        return [
            'account' => [
                'id' => $this->account->id,
                'name' => $this->account->name,
                'full_name' => $this->account->full_name ?? $this->account->name,
                'code' => $this->account->code,
                'currency_code' => $this->getCurrencyCode(),
                'type' => $this->account->accountType?->name,
            ],
            'current_balance' => $currentBalance->toDecimal(),
            'current_balance_formatted' => $this->formatMoney($currentBalance->toDecimal()),
            'date_range' => [
                'start_date' => $this->startDate?->toDateString(),
                'end_date' => $this->endDate?->toDateString(),
            ],
        ];
    }

    /**
     * Export register data for CSV/Excel.
     */
    public function toExportArray(): array
    {
        $items = $this->get();
        $rows = [];

        foreach ($items as $item) {
            $rows[] = [
                'date' => $item->transaction_date,
                'description' => $item->description,
                'memo' => $item->memo,
                'reference' => $item->num,
                'debit' => $item->action === Split::DEBIT
                    ? Money::fromFraction($item->amount_num, $item->amount_denom, $this->getCurrencyCode())->toDecimal()
                    : null,
                'credit' => $item->action === Split::CREDIT
                    ? Money::fromFraction($item->amount_num, $item->amount_denom, $this->getCurrencyCode())->toDecimal()
                    : null,
                'balance' => $item->running_balance,
                'reconciled' => $item->reconciled_state,
            ];
        }

        return $rows;
    }

    /**
     * Build the base query.
     */
    protected function buildQuery()
    {
        $query = DB::table('splits')
            ->join('transactions', 'splits.transaction_id', '=', 'transactions.id')
            ->leftJoin('splits as other_splits', function ($join) {
                $join->on('other_splits.transaction_id', '=', 'transactions.id')
                    ->on('other_splits.id', '!=', 'splits.id');
            })
            ->leftJoin('accounts as other_accounts', 'other_splits.account_id', '=', 'other_accounts.id')
            ->where('splits.account_id', $this->account->id);

        if (! $this->includeVoided) {
            $query->where('transactions.is_void', false);
        }

        // Date filtering
        if ($this->startDate) {
            $query->where(function ($q) {
                $q->whereDate('transactions.post_date', '>=', $this->startDate)
                    ->orWhere(function ($inner) {
                        $inner->whereNull('transactions.post_date')
                            ->whereDate('transactions.transaction_date', '>=', $this->startDate);
                    });
            });
        }

        if ($this->endDate) {
            $query->where(function ($q) {
                $q->whereDate('transactions.post_date', '<=', $this->endDate)
                    ->orWhere(function ($inner) {
                        $inner->whereNull('transactions.post_date')
                            ->whereDate('transactions.transaction_date', '<=', $this->endDate);
                    });
            });
        }

        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('transactions.description', 'like', "%{$this->search}%")
                    ->orWhere('splits.memo', 'like', "%{$this->search}%")
                    ->orWhere('transactions.num', 'like', "%{$this->search}%");
            });
        }

        // Select fields
        $query->select([
            'splits.id as split_id',
            'splits.transaction_id',
            'splits.action',
            'splits.amount_num',
            'splits.amount_denom',
            'splits.memo',
            'splits.reconciled_state',
            'transactions.transaction_date',
            'transactions.post_date',
            'transactions.description',
            'transactions.num',
            'transactions.is_void',
            DB::raw('GROUP_CONCAT(DISTINCT other_accounts.name) as transfer_account'),
        ])
            ->groupBy([
                'splits.id',
                'splits.transaction_id',
                'splits.action',
                'splits.amount_num',
                'splits.amount_denom',
                'splits.memo',
                'splits.reconciled_state',
                'transactions.transaction_date',
                'transactions.post_date',
                'transactions.description',
                'transactions.num',
                'transactions.is_void',
            ])
            ->orderBy('transactions.post_date', $this->sortDirection)
            ->orderBy('transactions.transaction_date', $this->sortDirection)
            ->orderBy('transactions.created_at', $this->sortDirection);

        return $query;
    }

    /**
     * Calculate starting balance for a page of results.
     */
    protected function calculateStartingBalance($items): Money
    {
        if (empty($items)) {
            return Money::zero($this->getCurrencyCode());
        }

        // Get the first transaction date on this page
        $firstItem = $items[0] ?? null;
        if (! $firstItem) {
            return Money::zero($this->getCurrencyCode());
        }

        $firstDate = $firstItem->post_date ?? $firstItem->transaction_date;

        // Sum all transactions before this one
        $result = DB::table('splits')
            ->join('transactions', 'splits.transaction_id', '=', 'transactions.id')
            ->where('splits.account_id', $this->account->id)
            ->where('transactions.is_void', false)
            ->where(function ($q) use ($firstDate, $firstItem) {
                $q->where(function ($inner) use ($firstDate) {
                    $inner->whereDate('transactions.post_date', '<', $firstDate);
                })
                    ->orWhere(function ($inner) use ($firstDate, $firstItem) {
                        $inner->whereDate('transactions.post_date', '=', $firstDate)
                            ->where('transactions.created_at', '<', $firstItem->created_at ?? now());
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
     * Get the currency code for this account.
     */
    protected function getCurrencyCode(): string
    {
        return $this->account->currency?->code ?? 'USD';
    }

    /**
     * Format a money value.
     */
    protected function formatMoney($amount): string
    {
        $currencyCode = $this->getCurrencyCode();

        return number_format($amount, 2) . ' ' . $currencyCode;
    }

    /**
     * Format the amount with debit/credit indication.
     */
    protected function formatAmount($item): string
    {
        $money = Money::fromFraction($item->amount_num, $item->amount_denom, $this->getCurrencyCode());
        $formatted = number_format($money->toDecimal(), 2);

        if ($item->action === Split::DEBIT) {
            return $formatted . ' DR';
        }

        return $formatted . ' CR';
    }
}
