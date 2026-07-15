<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxResource\Pages;
use App\Models\Tax;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class TaxResource extends Resource
{
    protected static ?string $model = Tax::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 70;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tax Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., VAT 16%, WHT 5%'),

                        TextInput::make('rate')
                            ->label('Rate (%)')
                            ->required()
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01),

                        Select::make('type')
                            ->options([
                                'percentage' => 'Percentage',
                                'fixed' => 'Fixed Amount',
                                'compound' => 'Compound',
                            ])
                            ->required()
                            ->default('percentage'),

                        Toggle::make('is_compound')
                            ->label('Compound Tax')
                            ->helperText('Calculate on subtotal + other taxes'),

                        Toggle::make('is_recoverable')
                            ->label('Recoverable (Input Tax)')
                            ->helperText('Can be claimed back (e.g., VAT input)'),

                        Select::make('account_id')
                            ->label('Tax Liability Account')
                            ->relationship('account', 'name', fn ($query) => $query
                                ->whereHas('accountType', fn ($q) => $q->where('type', 'liability'))
                            )
                            ->searchable()
                            ->preload()
                            ->helperText('Account where collected tax is posted'),

                        Toggle::make('enabled')
                            ->label('Enabled')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('Auto-Apply Rules')
                    ->schema([
                        Repeater::make('rules')
                            ->relationship()
                            ->schema([
                                Select::make('applies_to')
                                    ->options([
                                        'sales' => 'Sales (Invoices)',
                                        'purchases' => 'Purchases (Bills)',
                                        'both' => 'Both',
                                    ])
                                    ->required()
                                    ->default('both'),

                                Select::make('account_id')
                                    ->label('Specific Account')
                                    ->relationship('account', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('All accounts'),

                                Select::make('contact_type')
                                    ->label('Contact Type')
                                    ->options([
                                        'customer' => 'Customers',
                                        'vendor' => 'Vendors',
                                    ])
                                    ->placeholder('All contacts'),

                                TextInput::make('region')
                                    ->label('Region/Jurisdiction')
                                    ->maxLength(100)
                                    ->placeholder('e.g., Nairobi, International'),

                                TextInput::make('priority')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->helperText('Lower = higher priority'),
                            ])
                            ->columns(5)
                            ->defaultItems(0)
                            ->addActionLabel('Add Rule')
                            ->collapsible(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rate')
                    ->label('Rate')
                    ->getStateUsing(fn ($record) => number_format($record->rate_num / $record->rate_denom * 100, 2) . '%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'percentage' => 'info',
                        'fixed' => 'warning',
                        'compound' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_compound')
                    ->label('Compound')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_recoverable')
                    ->label('Recoverable')
                    ->boolean(),

                Tables\Columns\TextColumn::make('account.name')
                    ->label('Liability Account')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('enabled')
                    ->label('Enabled')
                    ->boolean(),

                Tables\Columns\TextColumn::make('rules_count')
                    ->counts('rules')
                    ->label('Rules'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'percentage' => 'Percentage',
                        'fixed' => 'Fixed',
                        'compound' => 'Compound',
                    ]),

                Tables\Filters\TernaryFilter::make('enabled')
                    ->label('Enabled')
                    ->boolean()
                    ->trueLabel('Enabled only')
                    ->falseLabel('Disabled only')
                    ->placeholder('All'),

                Tables\Filters\TernaryFilter::make('is_recoverable')
                    ->label('Recoverable')
                    ->boolean(),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
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
            'index' => Pages\ListTaxes::route('/'),
            'create' => Pages\CreateTax::route('/create'),
            'view' => Pages\ViewTax::route('/{record}'),
            'edit' => Pages\EditTax::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('enabled', true)->count();
    }
}
