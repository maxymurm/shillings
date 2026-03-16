<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExchangeRateResource\Pages;
use App\Models\ExchangeRate;
use BackedEnum;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class ExchangeRateResource extends Resource
{
    protected static ?string $model = ExchangeRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $navigationLabel = 'Exchange Rates';

    protected static string|UnitEnum|null $navigationGroup = 'Accounting';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Exchange Rate')
                    ->schema([
                        Select::make('from_currency_id')
                            ->label('From Currency')
                            ->relationship('fromCurrency', 'code')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} – {$record->name}"),

                        Select::make('to_currency_id')
                            ->label('To Currency')
                            ->relationship('toCurrency', 'code')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} – {$record->name}"),

                        TextInput::make('rate_decimal')
                            ->label('Rate')
                            ->numeric()
                            ->required()
                            ->step(0.000001)
                            ->placeholder('e.g., 1.234567')
                            ->helperText('How many units of "To Currency" equal 1 unit of "From Currency"')
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($state, $record, $set) {
                                if ($record && $record->rate_denom > 0) {
                                    $set('rate_decimal', round($record->rate_num / $record->rate_denom, 6));
                                }
                            })
                            ->columnSpan(2),

                        DatePicker::make('effective_date')
                            ->label('Effective Date')
                            ->required()
                            ->default(today())
                            ->native(false),

                        TextInput::make('source')
                            ->label('Source')
                            ->placeholder('e.g., CBC, Manual, API')
                            ->maxLength(100),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fromCurrency.code')
                    ->label('From')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('toCurrency.code')
                    ->label('To')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('rate')
                    ->label('Rate')
                    ->getStateUsing(fn (ExchangeRate $record): string =>
                        $record->rate_denom > 0
                            ? number_format($record->rate_num / $record->rate_denom, 6)
                            : '—'
                    )
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('effective_date')
                    ->label('Effective Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('effective_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('from_currency_id')
                    ->label('From Currency')
                    ->relationship('fromCurrency', 'code'),
                Tables\Filters\SelectFilter::make('to_currency_id')
                    ->label('To Currency')
                    ->relationship('toCurrency', 'code'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
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
            'index'  => Pages\ListExchangeRates::route('/'),
            'create' => Pages\CreateExchangeRate::route('/create'),
            'edit'   => Pages\EditExchangeRate::route('/{record}/edit'),
        ];
    }
}
