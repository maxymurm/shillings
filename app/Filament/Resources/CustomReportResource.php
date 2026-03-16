<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomReportResource\Pages;
use App\Models\Account;
use App\Models\CustomReport;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CustomReportResource extends Resource
{
    protected static ?string $model = CustomReport::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Report Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Monthly Revenue Summary'),

                        Textarea::make('description')
                            ->rows(2)
                            ->placeholder('Optional description for this report')
                            ->columnSpanFull(),

                        Select::make('base_report')
                            ->label('Base Report Type')
                            ->options(CustomReport::getBaseReportTypes())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($set) => $set('columns', null)),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_favorite')
                                    ->label('Mark as Favorite'),

                                Toggle::make('is_shared')
                                    ->label('Share with Team'),
                            ]),
                    ])
                    ->columns(2),

                Section::make('Report Configuration')
                    ->schema([
                        Select::make('account_ids')
                            ->label('Filter Accounts')
                            ->multiple()
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
                            ->placeholder('All accounts (leave empty)')
                            ->helperText('Select specific accounts to include, or leave empty for all'),

                        CheckboxList::make('columns')
                            ->label('Columns to Display')
                            ->options(function (Get $get) {
                                $baseReport = $get('base_report') ?? CustomReport::TYPE_TRIAL_BALANCE;
                                return CustomReport::getAvailableColumns($baseReport);
                            })
                            ->columns(3)
                            ->bulkToggleable(),

                        Select::make('grouping')
                            ->label('Group By')
                            ->options([
                                'account_type' => 'Account Type',
                                'parent_account' => 'Parent Account',
                                'month' => 'Month',
                                'quarter' => 'Quarter',
                                'year' => 'Year',
                            ])
                            ->placeholder('No grouping'),
                    ]),

                Section::make('Date Range')
                    ->schema([
                        Select::make('date_range.type')
                            ->label('Date Range')
                            ->options([
                                'this_month' => 'This Month',
                                'last_month' => 'Last Month',
                                'this_quarter' => 'This Quarter',
                                'last_quarter' => 'Last Quarter',
                                'this_year' => 'This Year',
                                'last_year' => 'Last Year',
                                'custom' => 'Custom Date Range',
                            ])
                            ->default('this_month'),
                    ])
                    ->columns(2),
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
                    ->action(function (CustomReport $record) {
                        $record->toggleFavorite();
                    }),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold),

                TextColumn::make('base_report')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => CustomReport::getBaseReportTypes()[$state] ?? $state)
                    ->color('gray'),

                TextColumn::make('description')
                    ->limit(30)
                    ->toggleable(),

                TextColumn::make('run_count')
                    ->label('Runs')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('last_run_at')
                    ->label('Last Run')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->placeholder('Never'),

                IconColumn::make('is_shared')
                    ->label('Shared')
                    ->boolean()
                    ->trueIcon('heroicon-o-user-group')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('info')
                    ->falseColor('gray'),

                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('is_favorite', 'desc')
            ->filters([
                SelectFilter::make('base_report')
                    ->label('Report Type')
                    ->options(CustomReport::getBaseReportTypes()),

                TernaryFilter::make('is_favorite')
                    ->label('Favorites')
                    ->placeholder('All Reports')
                    ->trueLabel('Favorites Only')
                    ->falseLabel('Non-Favorites'),

                TernaryFilter::make('is_shared')
                    ->label('Shared')
                    ->placeholder('All Reports')
                    ->trueLabel('Shared Only')
                    ->falseLabel('Private Only'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('run')
                        ->label('Run Report')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->action(function (CustomReport $record) {
                            $record->recordRun();
                            
                            // Redirect to reports page with this report's config
                            return redirect()->route('filament.admin.pages.reports', [
                                'report_type' => $record->base_report,
                            ]);
                        }),

                    EditAction::make(),

                    Action::make('duplicate')
                        ->label('Duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->action(function (CustomReport $record) {
                            $record->duplicate();
                            
                            Notification::make()
                                ->title('Report duplicated')
                                ->success()
                                ->send();
                        }),

                    Action::make('toggle_shared')
                        ->label(fn (CustomReport $record) => $record->is_shared ? 'Make Private' : 'Share')
                        ->icon(fn (CustomReport $record) => $record->is_shared ? 'heroicon-o-lock-closed' : 'heroicon-o-share')
                        ->color('gray')
                        ->action(function (CustomReport $record) {
                            $record->toggleShared();
                            
                            Notification::make()
                                ->title($record->is_shared ? 'Report shared' : 'Report made private')
                                ->success()
                                ->send();
                        }),

                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('No saved reports')
            ->emptyStateDescription('Create custom reports to save your favorite configurations.')
            ->emptyStateIcon('heroicon-o-document-magnifying-glass');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomReports::route('/'),
            'create' => Pages\CreateCustomReport::route('/create'),
            'edit' => Pages\EditCustomReport::route('/{record}/edit'),
        ];
    }
}
