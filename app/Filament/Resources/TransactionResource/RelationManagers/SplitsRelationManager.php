<?php

namespace App\Filament\Resources\TransactionResource\RelationManagers;

use App\Models\Account;
use App\Models\Split;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Schemas\Schema;

class SplitsRelationManager extends RelationManager
{
    protected static string $relationship = 'splits';

    protected static ?string $title = 'Split Entries';

    protected static ?string $recordTitleAttribute = 'memo';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
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
                            ->required(),

                        Select::make('action')
                            ->label('Type')
                            ->options([
                                Split::DEBIT => 'Debit',
                                Split::CREDIT => 'Credit',
                            ])
                            ->required()
                            ->default(Split::DEBIT),

                        TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01),

                        TextInput::make('memo')
                            ->label('Memo')
                            ->maxLength(255),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account.name')
                    ->label('Account')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('account.code')
                    ->label('Code')
                    ->sortable(),

                TextColumn::make('action')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => $state === Split::DEBIT ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state): string => $state === Split::DEBIT ? 'Debit' : 'Credit'),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->numeric(2)
                    ->alignEnd(),

                TextColumn::make('memo')
                    ->label('Memo')
                    ->limit(30)
                    ->toggleable(),

                TextColumn::make('reconciled_state')
                    ->label('Reconciled')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Split::RECONCILED_YES => 'success',
                        Split::RECONCILED_CLEARED => 'info',
                        Split::RECONCILED_FROZEN => 'gray',
                        Split::RECONCILED_VOID => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Split::RECONCILED_YES => 'Reconciled',
                        Split::RECONCILED_CLEARED => 'Cleared',
                        Split::RECONCILED_FROZEN => 'Frozen',
                        Split::RECONCILED_VOID => 'Void',
                        default => 'Not Reconciled',
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn () => $this->getOwnerRecord()->canEdit()),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn () => $this->getOwnerRecord()->canEdit()),
                DeleteAction::make()
                    ->visible(fn () => $this->getOwnerRecord()->canEdit()),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->visible(fn () => $this->getOwnerRecord()->canEdit()),
            ]);
    }

    public function isReadOnly(): bool
    {
        return ! $this->getOwnerRecord()->canEdit();
    }
}
