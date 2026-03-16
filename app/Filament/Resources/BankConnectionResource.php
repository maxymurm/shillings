<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankConnectionResource\Pages;
use App\Models\BankConnection;
use BackedEnum;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class BankConnectionResource extends Resource
{
    protected static ?string $model = BankConnection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $navigationLabel = 'Bank Connections';

    protected static string|UnitEnum|null $navigationGroup = 'Banking';

    protected static ?int $navigationSort = 50;

    protected static ?string $recordTitleAttribute = 'institution_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Connection Details')
                    ->schema([
                        Select::make('account_id')
                            ->label('Linked Account')
                            ->relationship('account', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->getOptionLabelFromRecordUsing(fn ($record) =>
                                ($record->code ? "[{$record->code}] " : '') . $record->name
                            ),

                        TextInput::make('institution_name')
                            ->label('Bank / Institution Name')
                            ->required()
                            ->maxLength(255),

                        Select::make('provider')
                            ->label('Provider')
                            ->options([
                                'manual' => 'Manual (CSV/OFX Import)',
                                'ofx'    => 'OFX Direct Connect',
                                'plaid'  => 'Plaid',
                                'yodlee' => 'Yodlee',
                                'other'  => 'Other',
                            ])
                            ->required()
                            ->default('manual'),

                        Toggle::make('enabled')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('institution_name')
                    ->label('Institution')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('account.name')
                    ->label('Linked Account')
                    ->searchable(),

                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'plaid'  => 'success',
                        'yodlee' => 'info',
                        'ofx'    => 'warning',
                        default  => 'gray',
                    }),

                Tables\Columns\TextColumn::make('sync_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'active'  => 'success',
                        'error'   => 'danger',
                        'pending' => 'warning',
                        default   => 'gray',
                    })
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('last_sync_at')
                    ->label('Last Synced')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),

                Tables\Columns\IconColumn::make('enabled')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('enabled')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->placeholder('All'),
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
            'index'  => Pages\ListBankConnections::route('/'),
            'create' => Pages\CreateBankConnection::route('/create'),
            'edit'   => Pages\EditBankConnection::route('/{record}/edit'),
        ];
    }
}
