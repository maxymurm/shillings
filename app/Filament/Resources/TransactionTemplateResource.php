<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionTemplateResource\Pages;
use App\Models\Account;
use App\Models\Split;
use App\Models\TransactionTemplate;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class TransactionTemplateResource extends Resource
{
    protected static ?string $model = TransactionTemplate::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static string|UnitEnum|null $navigationGroup = 'Transactions';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Monthly Rent Payment'),

                        Textarea::make('description')
                            ->rows(2)
                            ->placeholder('Optional description for this template')
                            ->columnSpanFull(),

                        Select::make('currency_id')
                            ->relationship('currency', 'code')
                            ->preload()
                            ->searchable(),

                        Toggle::make('is_favorite')
                            ->label('Mark as Favorite')
                            ->helperText('Favorite templates appear at the top of the list'),
                    ])
                    ->columns(2),

                Section::make('Split Entries')
                    ->description('Define the accounts and amounts for this template')
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
                                            ->minValue(0.01)
                                            ->step(0.01)
                                            ->placeholder('0.00'),
                                    ]),

                                TextInput::make('memo')
                                    ->label('Memo')
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
                IconColumn::make('is_favorite')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->action(function (TransactionTemplate $record) {
                        $record->toggleFavorite();
                    }),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold),

                TextColumn::make('description')
                    ->limit(40)
                    ->toggleable(),

                TextColumn::make('splits_count')
                    ->label('Splits')
                    ->getStateUsing(fn (TransactionTemplate $record) => count($record->splits_data ?? [])),

                TextColumn::make('use_count')
                    ->label('Times Used')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('last_used_at')
                    ->label('Last Used')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->placeholder('Never'),

                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('is_favorite', 'desc')
            ->filters([
                TernaryFilter::make('is_favorite')
                    ->label('Favorites Only')
                    ->placeholder('All Templates')
                    ->trueLabel('Favorites Only')
                    ->falseLabel('Non-Favorites Only'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('use')
                        ->label('Create Transaction')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->form([
                            TextInput::make('description')
                                ->label('Transaction Description')
                                ->placeholder('Leave blank to use template name'),
                        ])
                        ->action(function (TransactionTemplate $record, array $data) {
                            $transaction = $record->createTransaction($data['description'] ?? null);
                            
                            Notification::make()
                                ->title('Transaction created from template')
                                ->body("Created: {$transaction->description}")
                                ->success()
                                ->send();

                            return redirect()->route('filament.admin.resources.transactions.edit', $transaction);
                        }),

                    EditAction::make(),

                    Action::make('duplicate')
                        ->label('Duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->action(function (TransactionTemplate $record) {
                            $newTemplate = $record->replicate(['use_count', 'last_used_at']);
                            $newTemplate->name = 'Copy of ' . $record->name;
                            $newTemplate->is_favorite = false;
                            $newTemplate->save();

                            Notification::make()
                                ->title('Template duplicated')
                                ->success()
                                ->send();
                        }),

                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('No templates yet')
            ->emptyStateDescription('Create templates to speed up recurring transaction entry.')
            ->emptyStateIcon('heroicon-o-document-duplicate');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactionTemplates::route('/'),
            'create' => Pages\CreateTransactionTemplate::route('/create'),
            'edit' => Pages\EditTransactionTemplate::route('/{record}/edit'),
        ];
    }
}
