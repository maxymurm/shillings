<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers\SplitsRelationManager;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Split;
use App\Models\Transaction;
use App\Services\TransactionService;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Transactions';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'description';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Transaction Details')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('transaction_date')
                                    ->label('Date')
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->displayFormat('M d, Y'),

                                TextInput::make('reference')
                                    ->label('Reference #')
                                    ->placeholder('e.g., INV-001, CHK-123')
                                    ->maxLength(50),

                                Select::make('currency_id')
                                    ->label('Currency')
                                    ->relationship('currency', 'code')
                                    ->preload()
                                    ->searchable()
                                    ->default(function () {
                                        return Currency::where('code', 'KES')->first()?->id;
                                    }),
                            ]),

                        TextInput::make('description')
                            ->label('Description')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Payment for office supplies')
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->placeholder('Additional notes or details...')
                            ->columnSpanFull(),
                    ]),

                Section::make('Split Entries')
                    ->description('Each transaction must have at least 2 splits that balance (Debits = Credits)')
                    ->schema([
                        static::getSplitsRepeater(),
                        
                        static::getBalanceDisplay(),
                    ]),
            ]);
    }

    protected static function getSplitsRepeater(): Repeater
    {
        return Repeater::make('splits')
            ->relationship()
            ->schema([
                Grid::make(5)
                    ->schema([
                        Select::make('account_id')
                            ->label('Account')
                            ->options(function () {
                                return Account::query()
                                    ->whereNotNull('account_type_id')
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
                            ->columnSpan(2),

                        Select::make('action')
                            ->label('Type')
                            ->options([
                                Split::DEBIT => 'Debit',
                                Split::CREDIT => 'Credit',
                            ])
                            ->required()
                            ->default(Split::DEBIT)
                            ->live()
                            ->columnSpan(1),

                        TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->live(onBlur: true)
                            ->columnSpan(1),

                        TextInput::make('memo')
                            ->label('Memo')
                            ->maxLength(255)
                            ->placeholder('Split memo...')
                            ->columnSpan(1),
                    ]),
            ])
            ->defaultItems(2)
            ->minItems(2)
            ->addActionLabel('Add Split')
            ->reorderable(false)
            ->collapsible()
            ->cloneable()
            ->itemLabel(function (array $state): ?string {
                $action = $state['action'] ?? Split::DEBIT;
                $amount = $state['amount'] ?? 0;
                $type = $action === Split::DEBIT ? 'DR' : 'CR';
                return "{$type}: " . number_format((float) $amount, 2);
            });
    }

    protected static function getBalanceDisplay(): Placeholder
    {
        return Placeholder::make('balance_check')
            ->label('')
            ->content(function (Get $get): HtmlString {
                $splits = $get('splits') ?? [];
                
                $debits = 0;
                $credits = 0;
                
                foreach ($splits as $split) {
                    $amount = (float) ($split['amount'] ?? 0);
                    $action = $split['action'] ?? Split::DEBIT;
                    
                    if ($action === Split::DEBIT) {
                        $debits += $amount;
                    } else {
                        $credits += $amount;
                    }
                }
                
                $difference = abs($debits - $credits);
                $isBalanced = $difference < 0.01;
                
                $balanceClass = $isBalanced ? 'text-success-600' : 'text-danger-600';
                $balanceIcon = $isBalanced ? '✓' : '✗';
                $balanceText = $isBalanced ? 'Transaction is balanced' : "Out of balance by " . number_format($difference, 2);
                
                return new HtmlString("
                    <div class='flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg'>
                        <div class='grid grid-cols-3 gap-8 text-sm'>
                            <div>
                                <span class='text-gray-500 dark:text-gray-400'>Total Debits:</span>
                                <span class='ml-2 font-semibold'>" . number_format($debits, 2) . "</span>
                            </div>
                            <div>
                                <span class='text-gray-500 dark:text-gray-400'>Total Credits:</span>
                                <span class='ml-2 font-semibold'>" . number_format($credits, 2) . "</span>
                            </div>
                            <div class='{$balanceClass} font-medium'>
                                {$balanceIcon} {$balanceText}
                            </div>
                        </div>
                    </div>
                ");
            })
            ->live();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('reference')
                    ->label('Ref #')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (Transaction $record): ?string {
                        return strlen($record->description) > 50 ? $record->description : null;
                    }),

                TextColumn::make('splits_summary')
                    ->label('Accounts')
                    ->getStateUsing(function (Transaction $record): string {
                        return $record->splits
                            ->take(2)
                            ->map(fn ($split) => $split->account?->name)
                            ->filter()
                            ->implode(' ↔ ');
                    })
                    ->limit(40),

                TextColumn::make('total')
                    ->label('Amount')
                    ->getStateUsing(function (Transaction $record): string {
                        $total = $record->getTotal();
                        return $record->currency?->symbol . ' ' . number_format($total->toDecimal(), 2);
                    })
                    ->alignEnd()
                    ->weight(FontWeight::SemiBold),

                IconColumn::make('is_posted')
                    ->label('Posted')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (Transaction $record) => $record->getStatus())
                    ->color(fn (string $state): string => match ($state) {
                        'posted' => 'success',
                        'draft' => 'warning',
                        'void' => 'danger',
                        'reversed' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from_date')
                            ->label('From'),
                        DatePicker::make('to_date')
                            ->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from_date'] ?? null) {
                            $indicators['from_date'] = 'From: ' . $data['from_date'];
                        }
                        if ($data['to_date'] ?? null) {
                            $indicators['to_date'] = 'To: ' . $data['to_date'];
                        }
                        return $indicators;
                    }),

                SelectFilter::make('account')
                    ->label('Account')
                    ->options(function () {
                        return Account::query()
                            ->whereNotNull('account_type_id')
                            ->orderBy('code')
                            ->pluck('name', 'id');
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        return $query->whereHas('splits', function (Builder $query) use ($data) {
                            $query->where('account_id', $data['value']);
                        });
                    })
                    ->searchable(),

                TernaryFilter::make('is_posted')
                    ->label('Posted Status')
                    ->placeholder('All')
                    ->trueLabel('Posted Only')
                    ->falseLabel('Drafts Only'),

                SelectFilter::make('status')
                    ->options([
                        'posted' => 'Posted',
                        'draft' => 'Draft',
                        'void' => 'Void',
                        'reversed' => 'Reversed',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'posted' => $query->where('is_posted', true)->where('is_void', false)->whereNull('reversed_by_id'),
                            'draft' => $query->where('is_posted', false)->where('is_void', false),
                            'void' => $query->where('is_void', true),
                            'reversed' => $query->whereNotNull('reversed_by_id'),
                            default => $query,
                        };
                    }),

                Filter::make('amount_range')
                    ->form([
                        TextInput::make('min_amount')
                            ->label('Min Amount')
                            ->numeric()
                            ->placeholder('0.00'),
                        TextInput::make('max_amount')
                            ->label('Max Amount')
                            ->numeric()
                            ->placeholder('999999.99'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['min_amount'], function (Builder $query, $amount) {
                                $query->whereHas('splits', function (Builder $q) use ($amount) {
                                    $q->where('action', Split::DEBIT)
                                        ->whereRaw('(amount_num / amount_denom) >= ?', [$amount]);
                                });
                            })
                            ->when($data['max_amount'], function (Builder $query, $amount) {
                                $query->whereHas('splits', function (Builder $q) use ($amount) {
                                    $q->where('action', Split::DEBIT)
                                        ->whereRaw('(amount_num / amount_denom) <= ?', [$amount]);
                                });
                            });
                    }),
            ])
            ->filtersFormColumns(2)
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn (Transaction $record) => $record->canEdit()),

                    Action::make('post')
                        ->label('Post')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Post Transaction')
                        ->modalDescription('Once posted, this transaction cannot be edited. Are you sure?')
                        ->visible(fn (Transaction $record) => $record->canPost())
                        ->action(function (Transaction $record, TransactionService $service) {
                            $service->post($record);
                            Notification::make()
                                ->title('Transaction posted')
                                ->success()
                                ->send();
                        }),

                    Action::make('duplicate')
                        ->label('Duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->action(function (Transaction $record) {
                            $newTransaction = $record->replicate(['is_posted', 'posted_at', 'is_void', 'voided_at', 'reversed_by_id']);
                            $newTransaction->transaction_date = now();
                            $newTransaction->description = 'Copy of: ' . $record->description;
                            $newTransaction->save();

                            foreach ($record->splits as $split) {
                                $newSplit = $split->replicate(['reconciled_state', 'reconcile_date']);
                                $newSplit->transaction_id = $newTransaction->id;
                                $newSplit->reconciled_state = Split::RECONCILED_NOT;
                                $newSplit->save();
                            }

                            Notification::make()
                                ->title('Transaction duplicated')
                                ->success()
                                ->send();

                            return redirect()->route('filament.admin.resources.transactions.edit', $newTransaction);
                        }),

                    Action::make('reverse')
                        ->label('Reverse')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Reverse Transaction')
                        ->modalDescription('This will create a reversing entry with opposite debits/credits.')
                        ->visible(fn (Transaction $record) => $record->canReverse())
                        ->action(function (Transaction $record, TransactionService $service) {
                            $reversal = $service->reverse($record);
                            
                            Notification::make()
                                ->title('Reversal created')
                                ->body('A reversing entry has been created as a draft.')
                                ->success()
                                ->send();

                            return redirect()->route('filament.admin.resources.transactions.edit', $reversal);
                        }),

                    Action::make('void')
                        ->label('Void')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Void Transaction')
                        ->modalDescription('This will mark the transaction as void. This action cannot be undone.')
                        ->form([
                            Textarea::make('reason')
                                ->label('Reason for voiding')
                                ->required()
                                ->maxLength(500),
                        ])
                        ->visible(fn (Transaction $record) => $record->canVoid())
                        ->action(function (Transaction $record, array $data, TransactionService $service) {
                            $service->void($record, $data['reason']);
                            
                            Notification::make()
                                ->title('Transaction voided')
                                ->success()
                                ->send();
                        }),

                    DeleteAction::make()
                        ->visible(fn (Transaction $record) => $record->canDelete()),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('post_selected')
                        ->label('Post Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records, TransactionService $service) {
                            $posted = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                if ($record->canPost()) {
                                    $service->post($record);
                                    $posted++;
                                } else {
                                    $skipped++;
                                }
                            }

                            Notification::make()
                                ->title("Posted {$posted} transactions" . ($skipped > 0 ? ", skipped {$skipped}" : ''))
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make()
                        ->before(function (Collection $records) {
                            // Filter out posted/void transactions
                            return $records->filter(fn ($r) => $r->canDelete());
                        }),
                ]),
            ])
            ->emptyStateHeading('No transactions yet')
            ->emptyStateDescription('Start by creating your first transaction.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public static function getRelations(): array
    {
        return [
            SplitsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['description', 'reference', 'notes'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Date' => $record->transaction_date->format('M d, Y'),
            'Status' => $record->getStatus(),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $draftCount = static::getModel()::where('is_posted', false)->where('is_void', false)->count();
        return $draftCount > 0 ? (string) $draftCount : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
}
