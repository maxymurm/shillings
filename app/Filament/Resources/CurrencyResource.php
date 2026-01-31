<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurrencyResource\Pages;
use App\Models\Currency;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 110;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Currency Details')
                    ->schema([
                        TextInput::make('code')
                            ->label('Currency Code')
                            ->required()
                            ->maxLength(10)
                            ->unique(ignoreRecord: true)
                            ->helperText('ISO 4217 code (e.g., USD, EUR, GBP)'),

                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('symbol')
                            ->required()
                            ->maxLength(10)
                            ->helperText('Currency symbol (e.g., $, €, £)'),

                        TextInput::make('symbol_native')
                            ->label('Native Symbol')
                            ->maxLength(10)
                            ->helperText('Symbol used in native locale'),

                        TextInput::make('decimal_digits')
                            ->numeric()
                            ->default(2)
                            ->minValue(0)
                            ->maxValue(8)
                            ->required(),

                        TextInput::make('rounding')
                            ->numeric()
                            ->default(0)
                            ->helperText('Smallest unit for rounding (0 for none)'),
                    ])
                    ->columns(2),

                Section::make('Status')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive currencies are not shown in dropdowns'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('symbol')
                    ->label('Symbol')
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('decimal_digits')
                    ->label('Decimals')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('accounts_count')
                    ->label('Accounts')
                    ->counts('accounts')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('setDefault')
                    ->label('Set as Default')
                    ->icon(Heroicon::OutlinedStar)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Currency $record) {
                        $companyId = session('active_company_id');
                        if ($companyId) {
                            \App\Models\Company::where('id', $companyId)
                                ->update(['default_currency_id' => $record->id]);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCurrencies::route('/'),
            'create' => Pages\CreateCurrency::route('/create'),
            'edit' => Pages\EditCurrency::route('/{record}/edit'),
        ];
    }
}
