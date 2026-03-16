<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImportBatchResource\Pages;
use App\Models\ImportBatch;
use BackedEnum;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class ImportBatchResource extends Resource
{
    protected static ?string $model = ImportBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?string $navigationLabel = 'Import History';

    protected static string|UnitEnum|null $navigationGroup = 'Banking';

    protected static ?int $navigationSort = 55;

    protected static bool $canCreate = false;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('file_name')
                    ->label('File')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('account.name')
                    ->label('Account')
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'ofx', 'qfx' => 'info',
                        'csv'         => 'success',
                        default       => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'failed'    => 'danger',
                        'pending'   => 'warning',
                        'processing' => 'info',
                        default      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_rows')
                    ->label('Rows')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_count')
                    ->label('Created')
                    ->numeric()
                    ->color('success'),

                Tables\Columns\TextColumn::make('matched_count')
                    ->label('Matched')
                    ->numeric()
                    ->color('info'),

                Tables\Columns\TextColumn::make('error_count')
                    ->label('Errors')
                    ->numeric()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('importedBy.name')
                    ->label('Imported By')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Imported At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'    => 'Pending',
                        'processing' => 'Processing',
                        'completed'  => 'Completed',
                        'failed'     => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'ofx' => 'OFX',
                        'qfx' => 'QFX',
                        'csv' => 'CSV',
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImportBatches::route('/'),
            'view'  => Pages\ViewImportBatch::route('/{record}'),
        ];
    }
}
