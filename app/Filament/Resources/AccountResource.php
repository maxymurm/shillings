<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Models\Account;
use App\Models\Company;
use App\Services\AccountService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use UnitEnum;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Accounting';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Details')
                    ->schema([
                        TextInput::make('code')
                            ->label('Account Code')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule) {
                                $companyId = session('active_company_id');

                                return $rule->where('company_id', $companyId);
                            })
                            ->helperText('Unique identifier for this account (e.g., 1000, 4100)'),

                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Select::make('account_type_id')
                            ->label('Account Type')
                            ->relationship('accountType', 'name')
                            ->required()
                            ->preload()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (\Filament\Schemas\Set $set) => $set('parent_id', null)),

                        Select::make('parent_id')
                            ->label('Parent Account')
                            ->relationship(
                                'parent',
                                'name',
                                fn (Builder $query, \Filament\Schemas\Get $get) => $query
                                    ->where('company_id', session('active_company_id'))
                                    ->when(
                                        $get('account_type_id'),
                                        fn ($q, $typeId) => $q->where('account_type_id', $typeId)
                                    )
                                    ->where('is_placeholder', true)
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->getOptionLabelFromRecordUsing(fn (Account $record) => "{$record->code} - {$record->name}"),

                        Select::make('currency_id')
                            ->label('Currency')
                            ->relationship('currency', 'code')
                            ->searchable()
                            ->preload()
                            ->default(fn () => Company::find(session('active_company_id'))?->default_currency_id),

                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpan('full'),
                    ])
                    ->columns(2),

                Section::make('Settings')
                    ->schema([
                        Toggle::make('is_placeholder')
                            ->label('Is Placeholder (Container)')
                            ->helperText('Placeholder accounts group other accounts and cannot have transactions')
                            ->default(false),

                        Toggle::make('is_hidden')
                            ->label('Hidden')
                            ->helperText('Hidden accounts are not shown in reports and dropdowns')
                            ->default(false),
                    ])
                    ->columns(2),
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
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Account Name')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->formatStateUsing(function (Account $record) {
                        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $record->depth ?? 0);
                        $icon = $record->is_placeholder
                            ? '<span class="text-gray-400 mr-1">📁</span>'
                            : '<span class="text-gray-400 mr-1">📄</span>';

                        return new HtmlString($indent . $icon . e($record->name));
                    }),

                Tables\Columns\TextColumn::make('accountType.name')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Asset' => 'success',
                        'Liability' => 'warning',
                        'Equity' => 'info',
                        'Income' => 'primary',
                        'Expense' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency.code')
                    ->label('Currency')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->getStateUsing(function (Account $record): string {
                        $balance = app(AccountService::class)->getBalance($record);
                        $decimal = is_array($balance) ? $balance['decimal'] : $balance;

                        return number_format($decimal, 2) . ' ' . ($record->currency?->code ?? 'USD');
                    })
                    ->alignEnd()
                    ->fontFamily('mono'),

                Tables\Columns\IconColumn::make('is_placeholder')
                    ->label('Container')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedFolder)
                    ->falseIcon(Heroicon::OutlinedDocument),

                Tables\Columns\IconColumn::make('is_hidden')
                    ->label('Hidden')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedEyeSlash)
                    ->falseIcon(Heroicon::OutlinedEye)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('code')
            ->filters([
                Tables\Filters\SelectFilter::make('account_type_id')
                    ->label('Account Type')
                    ->relationship('accountType', 'name')
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_placeholder')
                    ->label('Type')
                    ->boolean()
                    ->trueLabel('Containers only')
                    ->falseLabel('Accounts only')
                    ->native(false),

                Tables\Filters\TernaryFilter::make('is_hidden')
                    ->label('Visibility')
                    ->boolean()
                    ->trueLabel('Hidden only')
                    ->falseLabel('Visible only')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\Action::make('addChild')
                    ->label('Add Child')
                    ->icon(Heroicon::OutlinedPlus)
                    ->color('gray')
                    ->visible(fn (Account $record) => $record->is_placeholder)
                    ->url(fn (Account $record) => static::getUrl('create', [
                        'parent_id' => $record->id,
                        'account_type_id' => $record->account_type_id,
                    ])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Account $record) {
                        if ($record->children()->exists()) {
                            throw new \Exception('Cannot delete account with children. Delete children first.');
                        }
                        if ($record->splits()->exists()) {
                            throw new \Exception('Cannot delete account with transactions.');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('hide')
                        ->label('Hide Selected')
                        ->icon(Heroicon::OutlinedEyeSlash)
                        ->action(fn ($records) => $records->each->update(['is_hidden' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('show')
                        ->label('Show Selected')
                        ->icon(Heroicon::OutlinedEye)
                        ->action(fn ($records) => $records->each->update(['is_hidden' => false]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $companyId = session('active_company_id');

        return parent::getEloquentQuery()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with(['accountType', 'currency', 'parent'])
            ->withCount('children')
            ->orderBy('code');
    }
}
