<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\Split;
use App\Services\AccountService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Tables\Columns\CheckboxColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ReconciliationPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static ?string $navigationLabel = 'Bank Reconciliation';

    protected static string|UnitEnum|null $navigationGroup = 'Transactions';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.reconciliation';

    public ?string $account_id = null;
    public ?string $statement_date = null;
    public ?float $statement_balance = null;
    public ?float $opening_balance = null;

    public function mount(): void
    {
        $this->statement_date = now()->format('Y-m-d');
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Reconciliation Settings')
                ->schema([
                    Select::make('account_id')
                        ->label('Account to Reconcile')
                        ->options(function () {
                            return Account::query()
                                ->whereHas('accountType', function ($query) {
                                    $query->whereIn('name', ['Asset', 'Liability']);
                                })
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(fn ($account) => [
                                    $account->id => $account->code 
                                        ? "[{$account->code}] {$account->name}"
                                        : $account->name,
                                ]);
                        })
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state) {
                            if ($state) {
                                $this->loadAccountBalance($state);
                            }
                        }),

                    DatePicker::make('statement_date')
                        ->label('Statement Date')
                        ->required()
                        ->native(false)
                        ->default(now()),

                    TextInput::make('opening_balance')
                        ->label('Opening Balance')
                        ->numeric()
                        ->prefix('$')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('statement_balance')
                        ->label('Statement Ending Balance')
                        ->numeric()
                        ->required()
                        ->prefix('$')
                        ->live()
                        ->placeholder('Enter statement balance'),
                ])
                ->columns(4),
        ];
    }

    protected function loadAccountBalance(?string $accountId): void
    {
        if (!$accountId) {
            $this->opening_balance = 0;
            return;
        }

        $account = Account::find($accountId);
        if ($account) {
            $service = app(AccountService::class);
            $balance = $service->getBalance($account);
            $this->opening_balance = $balance->toDecimal();
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                if (!$this->account_id) {
                    return Split::query()->whereRaw('1 = 0');
                }

                return Split::query()
                    ->where('account_id', $this->account_id)
                    ->whereIn('reconciled_state', [Split::RECONCILED_NOT, Split::RECONCILED_CLEARED])
                    ->whereHas('transaction', function (Builder $query) {
                        $query->where('is_posted', true)
                            ->where('is_void', false);
                        
                        if ($this->statement_date) {
                            $query->where('transaction_date', '<=', $this->statement_date);
                        }
                    })
                    ->with(['transaction', 'account']);
            })
            ->columns([
                CheckboxColumn::make('is_cleared')
                    ->label('Clear')
                    ->getStateUsing(fn (Split $record): bool => $record->reconciled_state === Split::RECONCILED_CLEARED)
                    ->updateStateUsing(function (Split $record, bool $state): void {
                        $record->reconciled_state = $state ? Split::RECONCILED_CLEARED : Split::RECONCILED_NOT;
                        $record->save();
                    }),

                TextColumn::make('transaction.transaction_date')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('transaction.reference')
                    ->label('Ref #')
                    ->sortable(),

                TextColumn::make('transaction.description')
                    ->label('Description')
                    ->limit(40)
                    ->searchable(),

                TextColumn::make('memo')
                    ->label('Memo')
                    ->limit(20),

                TextColumn::make('debit')
                    ->label('Debit')
                    ->getStateUsing(function (Split $record): ?string {
                        if ($record->action === Split::DEBIT) {
                            return number_format($record->amount, 2);
                        }
                        return null;
                    })
                    ->alignEnd(),

                TextColumn::make('credit')
                    ->label('Credit')
                    ->getStateUsing(function (Split $record): ?string {
                        if ($record->action === Split::CREDIT) {
                            return number_format($record->amount, 2);
                        }
                        return null;
                    })
                    ->alignEnd(),

                TextColumn::make('reconciled_state')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Split::RECONCILED_CLEARED => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Split::RECONCILED_CLEARED => 'Cleared',
                        default => 'Pending',
                    }),
            ])
            ->defaultSort('transaction.transaction_date', 'desc')
            ->poll('5s')
            ->headerActions([
                Action::make('clear_all')
                    ->label('Clear All')
                    ->icon('heroicon-o-check')
                    ->color('gray')
                    ->action(function () {
                        Split::query()
                            ->where('account_id', $this->account_id)
                            ->where('reconciled_state', Split::RECONCILED_NOT)
                            ->whereHas('transaction', function (Builder $query) {
                                $query->where('is_posted', true)
                                    ->where('is_void', false);
                            })
                            ->update(['reconciled_state' => Split::RECONCILED_CLEARED]);
                        
                        Notification::make()
                            ->title('All items cleared')
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => $this->account_id !== null),

                Action::make('unclear_all')
                    ->label('Unclear All')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->action(function () {
                        Split::query()
                            ->where('account_id', $this->account_id)
                            ->where('reconciled_state', Split::RECONCILED_CLEARED)
                            ->update(['reconciled_state' => Split::RECONCILED_NOT]);
                        
                        Notification::make()
                            ->title('All items uncleared')
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => $this->account_id !== null),
            ])
            ->emptyStateHeading('No transactions to reconcile')
            ->emptyStateDescription('Select an account to begin reconciliation.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public function getClearedBalance(): float
    {
        if (!$this->account_id) {
            return 0;
        }

        $debits = Split::query()
            ->where('account_id', $this->account_id)
            ->where('reconciled_state', Split::RECONCILED_CLEARED)
            ->where('action', Split::DEBIT)
            ->whereHas('transaction', fn ($q) => $q->where('is_posted', true)->where('is_void', false))
            ->sum('amount_num');

        $credits = Split::query()
            ->where('account_id', $this->account_id)
            ->where('reconciled_state', Split::RECONCILED_CLEARED)
            ->where('action', Split::CREDIT)
            ->whereHas('transaction', fn ($q) => $q->where('is_posted', true)->where('is_void', false))
            ->sum('amount_num');

        // Assuming 100 as denominator for simplicity
        return ($debits - $credits) / 100;
    }

    public function getDifference(): float
    {
        if (!$this->statement_balance) {
            return 0;
        }

        $clearedBalance = $this->getClearedBalance();
        return $this->statement_balance - ($this->opening_balance + $clearedBalance);
    }

    public function isBalanced(): bool
    {
        return abs($this->getDifference()) < 0.01;
    }

    public function completeReconciliation(): void
    {
        if (!$this->isBalanced()) {
            Notification::make()
                ->title('Cannot complete reconciliation')
                ->body('The account does not balance. Difference: ' . number_format($this->getDifference(), 2))
                ->danger()
                ->send();
            return;
        }

        // Mark all cleared items as reconciled
        Split::query()
            ->where('account_id', $this->account_id)
            ->where('reconciled_state', Split::RECONCILED_CLEARED)
            ->update([
                'reconciled_state' => Split::RECONCILED_YES,
                'reconcile_date' => now(),
            ]);

        Notification::make()
            ->title('Reconciliation completed!')
            ->body('All cleared items have been marked as reconciled.')
            ->success()
            ->send();

        // Reset the form
        $this->statement_balance = null;
    }

    public function getViewData(): array
    {
        return [
            'clearedBalance' => $this->getClearedBalance(),
            'difference' => $this->getDifference(),
            'isBalanced' => $this->isBalanced(),
        ];
    }
}
