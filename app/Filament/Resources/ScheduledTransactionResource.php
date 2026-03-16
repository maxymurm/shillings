<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduledTransactionResource\Pages;
use App\Models\Account;
use App\Models\ScheduledTransaction;
use App\Models\Split;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ScheduledTransactionResource extends Resource
{
    protected static ?string $model = ScheduledTransaction::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Transactions';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Schedule Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Monthly Rent Payment'),

                        Textarea::make('description')
                            ->rows(2)
                            ->placeholder('Transaction description when created')
                            ->columnSpanFull(),

                        Select::make('currency_id')
                            ->relationship('currency', 'code')
                            ->preload()
                            ->searchable(),

                        Select::make('template_id')
                            ->label('Based on Template')
                            ->relationship('template', 'name')
                            ->placeholder('Select template to copy splits from')
                            ->preload()
                            ->searchable(),
                    ])
                    ->columns(2),

                Section::make('Schedule Settings')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('frequency')
                                    ->options(ScheduledTransaction::getFrequencies())
                                    ->required()
                                    ->default(ScheduledTransaction::FREQ_MONTHLY)
                                    ->live(),

                                TextInput::make('frequency_interval')
                                    ->label('Every N Periods')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(365)
                                    ->default(1)
                                    ->helperText('e.g., 2 = every 2 weeks/months'),

                                Select::make('day_of_month')
                                    ->label('Day of Month')
                                    ->options(collect(range(1, 31))->mapWithKeys(fn ($day) => [$day => $day]))
                                    ->visible(fn (Get $get) => in_array($get('frequency'), [
                                        ScheduledTransaction::FREQ_MONTHLY,
                                        ScheduledTransaction::FREQ_QUARTERLY,
                                        ScheduledTransaction::FREQ_YEARLY,
                                    ])),
                            ]),

                        Grid::make(3)
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label('Start Date')
                                    ->required()
                                    ->native(false)
                                    ->default(now()),

                                DatePicker::make('end_date')
                                    ->label('End Date')
                                    ->native(false)
                                    ->placeholder('Never'),

                                TextInput::make('max_occurrences')
                                    ->label('Max Occurrences')
                                    ->numeric()
                                    ->minValue(1)
                                    ->placeholder('Unlimited'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Toggle::make('auto_create')
                                    ->label('Auto-Create Transactions')
                                    ->helperText('Automatically create when due')
                                    ->default(true),

                                Toggle::make('auto_post')
                                    ->label('Auto-Post')
                                    ->helperText('Automatically post created transactions')
                                    ->default(false),

                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ]),
                    ]),

                Section::make('Split Entries')
                    ->description('Define the accounts and amounts for scheduled transactions')
                    ->schema([
                        Repeater::make('splits_data')
                            ->label('')
                            ->schema([
                                Grid::make(4)
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
                                            ->default(Split::DEBIT),

                                        TextInput::make('amount')
                                            ->numeric()
                                            ->required()
                                            ->minValue(0.01)
                                            ->step(0.01),
                                    ]),

                                TextInput::make('memo')
                                    ->maxLength(255)
                                    ->placeholder('Split memo...')
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(2)
                            ->minItems(2)
                            ->addActionLabel('Add Split')
                            ->collapsible()
                            ->itemLabel(function (array $state): ?string {
                                $action = $state['action'] ?? Split::DEBIT;
                                $amount = $state['amount'] ?? 0;
                                $type = $action === Split::DEBIT ? 'DR' : 'CR';
                                return "{$type}: " . number_format((float) $amount, 2);
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_active')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-play-circle')
                    ->falseIcon('heroicon-o-pause-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold),

                TextColumn::make('frequency')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state) => ScheduledTransaction::getFrequencies()[$state] ?? $state),

                TextColumn::make('next_occurrence')
                    ->label('Next Due')
                    ->date('M d, Y')
                    ->sortable()
                    ->color(fn (ScheduledTransaction $record) => $record->isDue() ? 'danger' : null),

                TextColumn::make('occurrences_created')
                    ->label('Created')
                    ->alignEnd(),

                IconColumn::make('auto_create')
                    ->label('Auto')
                    ->boolean()
                    ->trueIcon('heroicon-o-bolt')
                    ->falseIcon('heroicon-o-hand-raised')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                IconColumn::make('is_paused')
                    ->label('Paused')
                    ->boolean()
                    ->trueIcon('heroicon-o-pause')
                    ->falseIcon('heroicon-o-check')
                    ->trueColor('warning')
                    ->falseColor('success'),

                TextColumn::make('last_occurrence')
                    ->label('Last Created')
                    ->date('M d, Y')
                    ->placeholder('Never')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('next_occurrence', 'asc')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),

                SelectFilter::make('frequency')
                    ->options(ScheduledTransaction::getFrequencies()),

                TernaryFilter::make('auto_create')
                    ->label('Auto-Create')
                    ->placeholder('All')
                    ->trueLabel('Auto-Create')
                    ->falseLabel('Manual Only'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('create_now')
                        ->label('Create Now')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (ScheduledTransaction $record) => $record->is_active && !$record->is_paused)
                        ->action(function (ScheduledTransaction $record) {
                            $transaction = $record->createTransaction();
                            
                            if ($transaction) {
                                Notification::make()
                                    ->title('Transaction created')
                                    ->body("Created: {$transaction->description}")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Cannot create transaction')
                                    ->body('Schedule may have reached its limit or is not active.')
                                    ->warning()
                                    ->send();
                            }
                        }),

                    Action::make('skip')
                        ->label('Skip Next')
                        ->icon('heroicon-o-forward')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->visible(fn (ScheduledTransaction $record) => $record->is_active)
                        ->action(function (ScheduledTransaction $record) {
                            $oldDate = $record->next_occurrence->format('M d, Y');
                            $record->skip();
                            
                            Notification::make()
                                ->title('Occurrence skipped')
                                ->body("Skipped {$oldDate}. Next: {$record->next_occurrence->format('M d, Y')}")
                                ->success()
                                ->send();
                        }),

                    Action::make('pause')
                        ->label('Pause')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->visible(fn (ScheduledTransaction $record) => $record->is_active && !$record->is_paused)
                        ->action(function (ScheduledTransaction $record) {
                            $record->pause();
                            
                            Notification::make()
                                ->title('Schedule paused')
                                ->success()
                                ->send();
                        }),

                    Action::make('resume')
                        ->label('Resume')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->visible(fn (ScheduledTransaction $record) => $record->is_paused)
                        ->action(function (ScheduledTransaction $record) {
                            $record->resume();
                            
                            Notification::make()
                                ->title('Schedule resumed')
                                ->success()
                                ->send();
                        }),

                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('No scheduled transactions')
            ->emptyStateDescription('Set up recurring transactions to automate your bookkeeping.')
            ->emptyStateIcon('heroicon-o-clock');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScheduledTransactions::route('/'),
            'create' => Pages\CreateScheduledTransaction::route('/create'),
            'edit' => Pages\EditScheduledTransaction::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $dueCount = static::getModel()::due()->count();
        return $dueCount > 0 ? (string) $dueCount : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }
}
