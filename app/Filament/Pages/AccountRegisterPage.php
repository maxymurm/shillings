<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\Company;
use App\Models\Split;
use App\Models\Transaction;
use App\Services\TransactionService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class AccountRegisterPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static ?string $navigationLabel = 'Account Register';

    protected static string|UnitEnum|null $navigationGroup = 'Transactions';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.account-register';

    // Account selection
    public ?string $accountId = null;

    // Quick-entry form fields
    public string $entryDate = '';
    public string $entryNum = '';
    public string $entryDescription = '';
    public ?string $entryTransferAccountId = null;
    public string $entryDebit = '';
    public string $entryCredit = '';
    public string $entryMemo = '';

    // Description autocomplete
    public array $suggestions = [];

    // Register filters & sort
    public string $registerSearch          = '';
    public string $registerStartDate       = '';
    public string $registerEndDate         = '';
    public bool   $registerUnreconciledOnly = false;
    public string $registerSortColumn      = 'date';
    public string $registerSortDir         = 'desc';

    public function mount(): void
    {
        $this->entryDate = now()->format('Y-m-d');

        // Try to restore last-used account from session
        $last = session('register_account_id');
        if ($last && Account::find($last)) {
            $this->accountId = $last;
        }
    }

    public function updatedAccountId(): void
    {
        session(['register_account_id' => $this->accountId]);
        $this->resetEntry();
    }

    public function updatedEntryDescription(string $value): void
    {
        if (strlen($value) < 2 || ! $this->accountId) {
            $this->suggestions = [];
            return;
        }

        $companyId = session('active_company_id') ?? Company::first()?->id;

        $this->suggestions = Transaction::query()
            ->where('company_id', $companyId)
            ->where('description', 'like', "%{$value}%")
            ->whereHas('splits', fn ($q) => $q->where('account_id', $this->accountId))
            ->with('splits.account')
            ->orderByDesc('transaction_date')
            ->limit(6)
            ->get()
            ->map(function (Transaction $tx) {
                $mySplit    = $tx->splits->firstWhere('account_id', $this->accountId);
                $otherSplits = $tx->splits->where('account_id', '!=', $this->accountId);

                $transferAccountId   = $otherSplits->count() === 1 ? $otherSplits->first()?->account_id : null;
                $transferAccountName = $otherSplits->count() === 1
                    ? ($otherSplits->first()?->account?->name ?? '—')
                    : '-- Split --';

                $amount = $mySplit && $mySplit->amount_denom > 0
                    ? number_format($mySplit->amount_num / $mySplit->amount_denom, 2)
                    : '';

                return [
                    'description'          => $tx->description,
                    'transfer_account_id'  => $transferAccountId,
                    'transfer_account'     => $transferAccountName,
                    'debit'  => $mySplit?->action === Split::DEBIT  ? $amount : '',
                    'credit' => $mySplit?->action === Split::CREDIT ? $amount : '',
                ];
            })
            ->unique('description')
            ->values()
            ->toArray();
    }

    public function applySuggestion(int $index): void
    {
        $s = $this->suggestions[$index] ?? null;
        if (! $s) return;

        $this->entryDescription         = $s['description'];
        $this->entryTransferAccountId   = $s['transfer_account_id'];
        $this->entryDebit               = $s['debit'];
        $this->entryCredit              = $s['credit'];
        $this->suggestions              = [];
    }

    public function saveEntry(): void
    {
        $this->validate([
            'accountId'              => 'required',
            'entryDate'              => 'required|date',
            'entryDescription'       => 'required|string|min:1',
            'entryTransferAccountId' => 'required',
        ], [
            'accountId.required'              => 'Please select an account.',
            'entryDescription.required'       => 'Description is required.',
            'entryTransferAccountId.required' => 'Please select a transfer account.',
        ]);

        $debit  = $this->entryDebit  !== '' ? (float) $this->entryDebit  : null;
        $credit = $this->entryCredit !== '' ? (float) $this->entryCredit : null;

        if ($debit === null && $credit === null) {
            Notification::make()->title('Enter either a debit or credit amount.')->warning()->send();
            return;
        }
        if ($debit !== null && $credit !== null && $debit > 0 && $credit > 0) {
            Notification::make()->title('Enter only debit OR credit, not both.')->warning()->send();
            return;
        }

        $amount = $debit > 0 ? $debit : $credit;
        $action = $debit > 0 ? Split::DEBIT : Split::CREDIT;
        $oppositeAction = $action === Split::DEBIT ? Split::CREDIT : Split::DEBIT;

        $companyId = session('active_company_id') ?? Company::first()?->id;

        try {
            app(TransactionService::class)->create(
                [
                    'company_id'       => $companyId,
                    'transaction_date' => $this->entryDate,
                    'description'      => $this->entryDescription,
                    'num'              => $this->entryNum ?: null,
                    'created_by'       => auth()->id(),
                ],
                [
                    [
                        'account_id' => $this->accountId,
                        'amount'     => $amount,
                        'action'     => $action,
                        'memo'       => $this->entryMemo,
                    ],
                    [
                        'account_id' => $this->entryTransferAccountId,
                        'amount'     => $amount,
                        'action'     => $oppositeAction,
                        'memo'       => $this->entryMemo,
                    ],
                ]
            );

            Notification::make()->title('Transaction saved')->success()->send();

            // Keep date & account; clear rest
            $this->entryNum                = '';
            $this->entryDescription        = '';
            $this->entryTransferAccountId  = null;
            $this->entryDebit              = '';
            $this->entryCredit             = '';
            $this->entryMemo               = '';
            $this->suggestions             = [];

        } catch (\Throwable $e) {
            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
        }
    }

    public function getRegisterRows(): array
    {
        if (! $this->accountId) return [];

        $account = Account::with(['accountType', 'currency'])->find($this->accountId);
        if (! $account) return [];

        $isDebitNormal = strtoupper($account->accountType?->normal_balance ?? 'DEBIT') === 'DEBIT';

        $splits = Split::where('splits.account_id', $this->accountId)
            ->join('transactions', 'splits.transaction_id', '=', 'transactions.id')
            ->orderBy('transactions.transaction_date')
            ->orderBy('transactions.created_at')
            ->orderBy('splits.created_at')
            ->select('splits.*')
            ->with(['transaction', 'transaction.splits.account'])
            ->get();

        $balance = 0;
        $rows    = [];

        foreach ($splits as $split) {
            $amount = $split->amount_denom > 0
                ? $split->amount_num / $split->amount_denom
                : 0;

            $debit  = $split->action === Split::DEBIT  ? $amount : 0;
            $credit = $split->action === Split::CREDIT ? $amount : 0;

            $balance += $isDebitNormal ? ($debit - $credit) : ($credit - $debit);

            $otherSplits = $split->transaction->splits
                ->where('account_id', '!=', $this->accountId);

            $transferLabel = match (true) {
                $otherSplits->count() === 0 => '—',
                $otherSplits->count() === 1 => $otherSplits->first()?->account?->name ?? '—',
                default                     => '-- Split (' . $otherSplits->count() . ') --',
            };
            $transferId = $otherSplits->count() === 1 ? $otherSplits->first()?->account_id : null;

            $rows[] = [
                'split_id'       => $split->id,
                'transaction_id' => $split->transaction_id,
                'date'           => $split->transaction->transaction_date->format('Y-m-d'),
                'num'            => $split->transaction->num ?? '',
                'description'    => $split->transaction->description ?? '',
                'memo'           => $split->memo ?? '',
                'transfer'       => $transferLabel,
                'transfer_id'    => $transferId,
                'is_split'       => $otherSplits->count() > 1,
                'debit'          => $debit  > 0 ? number_format($debit,  2) : '',
                'credit'         => $credit > 0 ? number_format($credit, 2) : '',
                'balance'        => number_format(abs($balance), 2),
                'balance_neg'    => $balance < 0,
                'reconciled'     => $split->reconciled_state,
                'is_posted'      => $split->transaction?->is_posted ?? false,
                'is_void'        => $split->transaction?->is_void  ?? false,
            ];
        }

        // Apply filters (balance is already computed so running totals remain accurate)
        $filtered = array_values(array_filter($rows, function ($row) {
            if ($this->registerStartDate && $row['date'] < $this->registerStartDate) return false;
            if ($this->registerEndDate   && $row['date'] > $this->registerEndDate)   return false;
            if ($this->registerUnreconciledOnly && $row['reconciled'] !== 'n') return false;
            if ($this->registerSearch) {
                $q = mb_strtolower($this->registerSearch);
                $match = str_contains(mb_strtolower($row['description']), $q)
                      || str_contains(mb_strtolower($row['memo']),        $q)
                      || str_contains(mb_strtolower($row['transfer']),    $q)
                      || str_contains($row['num'],    $q)
                      || str_contains($row['debit'],  $q)
                      || str_contains($row['credit'], $q);
                if (! $match) return false;
            }
            return true;
        }));

        // Sort
        usort($filtered, function ($a, $b) {
            $dir = $this->registerSortDir === 'asc' ? 1 : -1;
            return $dir * match ($this->registerSortColumn) {
                'description' => strcmp($a['description'], $b['description']),
                'debit'       => (float) str_replace(',', '', $a['debit'])  <=> (float) str_replace(',', '', $b['debit']),
                'credit'      => (float) str_replace(',', '', $a['credit']) <=> (float) str_replace(',', '', $b['credit']),
                'balance'     => (($a['balance_neg'] ? -1 : 1) * (float) str_replace(',', '', $a['balance'])) <=>
                                 (($b['balance_neg'] ? -1 : 1) * (float) str_replace(',', '', $b['balance'])),
                default       => strcmp($a['date'], $b['date']),
            };
        });

        return $filtered;
    }

    public function getAccounts(): Collection
    {
        $companyId = session('active_company_id') ?? Company::first()?->id;

        return Account::with('accountType')
            ->where('company_id', $companyId)
            ->where('is_placeholder', false)
            ->orderBy('code')
            ->get();
    }

    public function getTransferAccounts(): Collection
    {
        $companyId = session('active_company_id') ?? Company::first()?->id;

        return Account::with('accountType')
            ->where('company_id', $companyId)
            ->where('is_placeholder', false)
            ->when($this->accountId, fn ($q) => $q->where('id', '!=', $this->accountId))
            ->orderBy('code')
            ->get();
    }

    public function getCurrentAccount(): ?Account
    {
        return $this->accountId ? Account::with('accountType')->find($this->accountId) : null;
    }

    /**
     * Returns accounts grouped by account-type name, each with a pre-computed balance.
     * Used for the accounts-list landing view.
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getAccountSummaries(): array
    {
        $companyId = session('active_company_id') ?? Company::first()?->id;

        $accounts = Account::with('accountType')
            ->where('company_id', $companyId)
            ->where('is_placeholder', false)
            ->orderBy('code')
            ->get();

        // One efficient query for all debit/credit sums
        $sums = DB::table('splits')
            ->select(
                'account_id',
                DB::raw("SUM(CASE WHEN action = 'DEBIT'  THEN amount_num ELSE 0 END) as debit_num"),
                DB::raw("SUM(CASE WHEN action = 'CREDIT' THEN amount_num ELSE 0 END) as credit_num"),
                DB::raw('MAX(amount_denom) as denom'),
                DB::raw('COUNT(*) as tx_count')
            )
            ->whereIn('account_id', $accounts->pluck('id'))
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $grouped = [];
        foreach ($accounts as $account) {
            $typeName      = $account->accountType?->name ?? 'Other';
            $isDebitNormal = strtoupper($account->accountType?->normal_balance ?? 'DEBIT') === 'DEBIT';

            $row   = $sums[$account->id] ?? null;
            $denom = ($row?->denom ?: 100);
            $dr    = ($row?->debit_num  ?? 0) / $denom;
            $cr    = ($row?->credit_num ?? 0) / $denom;
            $bal   = $isDebitNormal ? ($dr - $cr) : ($cr - $dr);

            $grouped[$typeName][] = [
                'id'          => $account->id,
                'code'        => $account->code,
                'name'        => $account->name,
                'currency'    => $account->currency?->code ?? '',
                'balance'     => number_format(abs($bal), 2),
                'balance_neg' => $bal < 0,
                'tx_count'    => (int) ($row?->tx_count ?? 0),
            ];
        }

        return $grouped;
    }

    // ── Register Actions ────────────────────────────────────────────

    public function sortBy(string $column): void
    {
        if ($this->registerSortColumn === $column) {
            $this->registerSortDir = $this->registerSortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->registerSortColumn = $column;
            $this->registerSortDir    = 'desc';
        }
    }

    public function setDatePreset(string $preset): void
    {
        match ($preset) {
            'today'      => [$this->registerStartDate, $this->registerEndDate] = [now()->toDateString(), now()->toDateString()],
            'this_week'  => [$this->registerStartDate, $this->registerEndDate] = [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
            'this_month' => [$this->registerStartDate, $this->registerEndDate] = [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
            'this_year'  => [$this->registerStartDate, $this->registerEndDate] = [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
            default      => [$this->registerStartDate, $this->registerEndDate] = ['', ''],
        };
    }

    public function toggleReconcile(int $splitId): void
    {
        $split = Split::find($splitId);
        if (! $split) return;

        $companyId = session('active_company_id') ?? Company::first()?->id;
        $belongs   = \App\Models\Account::where('id', $split->account_id)
                        ->where('company_id', $companyId)->exists();
        if (! $belongs) return;

        $split->reconciled_state = match ($split->reconciled_state) {
            'n'     => 'c',
            'c'     => 'y',
            default => 'n',
        };
        $split->save();
    }

    public function voidTransaction(int $transactionId): void
    {
        $companyId = session('active_company_id') ?? Company::first()?->id;
        $txn = Transaction::where('id', $transactionId)->where('company_id', $companyId)->first();
        if (! $txn) return;

        try {
            app(TransactionService::class)->void($txn);
            Notification::make()->title('Transaction voided')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
        }
    }

    public function duplicateTransaction(int $transactionId): void
    {
        $companyId = session('active_company_id') ?? Company::first()?->id;
        $txn = Transaction::where('id', $transactionId)
                    ->where('company_id', $companyId)
                    ->with('splits')
                    ->first();
        if (! $txn) return;

        try {
            $splitsData = $txn->splits->map(fn ($s) => [
                'account_id' => $s->account_id,
                'amount'     => $s->amount_denom > 0 ? $s->amount_num / $s->amount_denom : 0,
                'action'     => $s->action,
                'memo'       => $s->memo,
            ])->toArray();

            app(TransactionService::class)->create(
                [
                    'company_id'       => $companyId,
                    'transaction_date' => now()->format('Y-m-d'),
                    'description'      => $txn->description . ' (copy)',
                    'num'              => null,
                    'created_by'       => auth()->id(),
                ],
                $splitsData
            );
            Notification::make()->title('Transaction duplicated')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
        }
    }

    public function deleteTransaction(int $transactionId): void
    {
        $companyId = session('active_company_id') ?? Company::first()?->id;
        $txn = Transaction::where('id', $transactionId)->where('company_id', $companyId)->first();
        if (! $txn) return;

        try {
            $txn->splits()->delete();
            $txn->delete();
            Notification::make()->title('Transaction deleted')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
        }
    }

    public function exportCsv(): mixed
    {
        $account  = Account::find($this->accountId);
        $rows     = $this->getRegisterRows();
        $filename = 'register_' . ($account?->code ?? $account?->name ?? 'account') . '_' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['Date', 'Num', 'Description', 'Memo', 'Transfer Account', 'Debit', 'Credit', 'Balance', 'Reconciled', 'Status']);
            foreach ($rows as $row) {
                fputcsv($f, [
                    $row['date'],
                    $row['num'],
                    $row['description'],
                    $row['memo'],
                    $row['transfer'],
                    $row['debit'],
                    $row['credit'],
                    ($row['balance_neg'] ? '-' : '') . $row['balance'],
                    $row['reconciled'],
                    $row['is_void'] ? 'VOID' : ($row['is_posted'] ? 'Posted' : 'Draft'),
                ]);
            }
            fclose($f);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function resetEntry(): void
    {
        $this->entryDate               = now()->format('Y-m-d');
        $this->entryNum                = '';
        $this->entryDescription        = '';
        $this->entryTransferAccountId  = null;
        $this->entryDebit              = '';
        $this->entryCredit             = '';
        $this->entryMemo               = '';
        $this->suggestions             = [];
    }

    /**
     * Returns balance summary for the currently selected account (for the header card).
     */
    public function getCurrentAccountStats(): array
    {
        if (! $this->accountId) return ['balance' => '0.00', 'balance_neg' => false, 'tx_count' => 0, 'total_debit' => '0.00', 'total_credit' => '0.00'];

        $row = DB::table('splits')
            ->where('account_id', $this->accountId)
            ->selectRaw("SUM(CASE WHEN action = 'DEBIT'  THEN amount_num ELSE 0 END) as dr_num")
            ->selectRaw("SUM(CASE WHEN action = 'CREDIT' THEN amount_num ELSE 0 END) as cr_num")
            ->selectRaw('MAX(amount_denom) as denom')
            ->selectRaw('COUNT(*) as tx_count')
            ->first();

        $account       = Account::with('accountType')->find($this->accountId);
        $isDebitNormal = strtoupper($account?->accountType?->normal_balance ?? 'DEBIT') === 'DEBIT';

        $denom = $row?->denom ?: 100;
        $dr    = ($row?->dr_num ?? 0) / $denom;
        $cr    = ($row?->cr_num ?? 0) / $denom;
        $bal   = $isDebitNormal ? ($dr - $cr) : ($cr - $dr);

        return [
            'balance'      => number_format(abs($bal), 2),
            'balance_neg'  => $bal < 0,
            'tx_count'     => (int) ($row?->tx_count ?? 0),
            'total_debit'  => number_format($dr, 2),
            'total_credit' => number_format($cr, 2),
        ];
    }
}
