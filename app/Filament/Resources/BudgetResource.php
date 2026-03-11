<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BudgetResource\Pages;
use App\Models\Budget;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\DatePicker;
use Filament\Schemas\Components\Toggle;
use Filament\Schemas\Components\Repeater;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class BudgetResource extends Resource
{
    protected static ?string $model = Budget::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static string|UnitEnum|null $navigationGroup = 'Planning';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Budget Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., 2026 Operating Budget'),

                        TextInput::make('fiscal_year')
                            ->label('Fiscal Year')
                            ->required()
                            ->numeric()
                            ->default(date('Y'))
                            ->minValue(2000)
                            ->maxValue(2100),

                        Select::make('recurrence')
                            ->options([
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                                'annual' => 'Annual',
                            ])
                            ->required()
                            ->default('monthly')
                            ->live()
                            ->afterStateUpdated(fn (\Filament\Schemas\Set $set, $state) => $set('num_periods', match ($state) {
                                'monthly' => 12,
                                'quarterly' => 4,
                                'annual' => 1,
                                default => 12,
                            })),

                        TextInput::make('num_periods')
                            ->label('Number of Periods')
                            ->required()
                            ->numeric()
                            ->default(12)
                            ->minValue(1)
                            ->maxValue(52),

                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->required()
                            ->default(fn () => now()->startOfYear()),

                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->required()
                            ->default(fn () => now()->endOfYear()),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive budgets will not appear in reports'),
                    ])
                    ->columns(2),

                Section::make('Budget Allocations')
                    ->schema([
                        Repeater::make('accounts')
                            ->relationship()
                            ->schema([
                                Select::make('account_id')
                                    ->label('Account')
                                    ->relationship('account', 'name', fn ($query) => $query
                                        ->whereHas('accountType', fn ($q) => $q->whereIn('type', ['expense', 'income']))
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                TextInput::make('period_num')
                                    ->label('Period')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1),

                                TextInput::make('amount')
                                    ->label('Budget Amount')
                                    ->required()
                                    ->numeric()
                                    ->prefix('KES'),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Add Budget Line')
                            ->reorderableWithButtons()
                            ->collapsible(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('fiscal_year')
                    ->label('Year')
                    ->sortable(),

                Tables\Columns\TextColumn::make('recurrence')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'monthly' => 'info',
                        'quarterly' => 'warning',
                        'annual' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('num_periods')
                    ->label('Periods'),

                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('accounts_count')
                    ->counts('accounts')
                    ->label('Lines'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('fiscal_year')
                    ->options(fn () => Budget::distinct()->pluck('fiscal_year', 'fiscal_year')->toArray()),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->placeholder('All'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('clone')
                    ->label('Clone to New Year')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->form([
                        TextInput::make('new_year')
                            ->label('New Fiscal Year')
                            ->required()
                            ->numeric()
                            ->default(fn ($record) => $record->fiscal_year + 1),
                    ])
                    ->action(function ($record, array $data) {
                        $budgetService = app(\App\Services\BudgetService::class);
                        $newBudget = $budgetService->cloneForNewYear($record, $data['new_year']);
                        
                        return redirect()->to(BudgetResource::getUrl('edit', ['record' => $newBudget]));
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('fiscal_year', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBudgets::route('/'),
            'create' => Pages\CreateBudget::route('/create'),
            'view' => Pages\ViewBudget::route('/{record}'),
            'edit' => Pages\EditBudget::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }
}
