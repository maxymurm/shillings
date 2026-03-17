<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use App\Models\Currency;
use App\Services\ChartOfAccountsService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 100;

    protected static ?string $navigationLabel = 'Organisations';

    protected static ?string $modelLabel = 'organisation';

    protected static ?string $pluralModelLabel = 'organisations';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Organisation Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan('full'),

                        Select::make('default_currency_id')
                            ->label('Default Currency')
                            ->relationship('defaultCurrency', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->getOptionLabelFromRecordUsing(fn (Currency $record) => "{$record->code} - {$record->name}"),

                        Select::make('fiscal_year_end_month')
                            ->label('Fiscal Year End Month')
                            ->options([
                                1 => 'January',
                                2 => 'February',
                                3 => 'March',
                                4 => 'April',
                                5 => 'May',
                                6 => 'June',
                                7 => 'July',
                                8 => 'August',
                                9 => 'September',
                                10 => 'October',
                                11 => 'November',
                                12 => 'December',
                            ])
                            ->default(12)
                            ->required(),

                        Select::make('fiscal_year_end_day')
                            ->label('Fiscal Year End Day')
                            ->options(fn () => array_combine(range(1, 31), range(1, 31)))
                            ->default(31)
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Settings')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive organisations are hidden from the organisation switcher'),
                    ]),

                Section::make('Starter Chart of Accounts')
                    ->description('Optionally import a predefined set of accounts to get started quickly.')
                    ->schema([
                        Select::make('chart_of_accounts_template')
                            ->label('Template')
                            ->options(function () {
                                $options = ['none' => "No template — I'll set up accounts myself"];
                                try {
                                    foreach (app(ChartOfAccountsService::class)->getAvailableTemplates() as $t) {
                                        $options[$t['key']] = $t['name'] . ' — ' . $t['description'];
                                    }
                                } catch (\Throwable) {}
                                return $options;
                            })
                            ->default('none')
                            ->helperText('Pre-populates your chart of accounts. You can manage accounts manually at any time.'),
                    ])
                    ->hiddenOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('defaultCurrency.code')
                    ->label('Currency')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('fiscal_year_end')
                    ->label('Fiscal Year End')
                    ->getStateUsing(function (Company $record): string {
                        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                        $month = $months[($record->fiscal_year_end_month ?? 12) - 1];

                        return "{$month} " . ($record->fiscal_year_end_day ?? 31);
                    }),

                Tables\Columns\TextColumn::make('accounts_count')
                    ->label('Accounts')
                    ->counts('accounts')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Actions\Action::make('switch')
                    ->label('Switch')
                    ->icon(Heroicon::OutlinedArrowRightStartOnRectangle)
                    ->color('success')
                    ->action(function (Company $record) {
                        session(['active_company_id' => $record->id]);
                        redirect()->route('filament.admin.pages.dashboard');
                    }),
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
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('users', fn ($q) => $q->where('users.id', auth()->id()));
    }
}
