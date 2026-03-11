<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use App\ValueObjects\Money;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\DatePicker;
use Filament\Schemas\Components\Repeater;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Invoicing';

    protected static ?int $navigationSort = 30;

    protected static ?string $label = 'Invoice / Bill';

    protected static ?string $pluralLabel = 'Documents';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Document Details')
                    ->schema([
                        Select::make('type')
                            ->options([
                                'invoice' => 'Invoice',
                                'bill' => 'Bill',
                                'quote' => 'Quote',
                                'credit_note' => 'Credit Note',
                                'debit_note' => 'Debit Note',
                            ])
                            ->required()
                            ->default('invoice')
                            ->live(),

                        TextInput::make('document_number')
                            ->label('Document Number')
                            ->maxLength(50)
                            ->placeholder('Auto-generated if empty'),

                        Select::make('contact_id')
                            ->label('Contact')
                            ->relationship('contact', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                                TextInput::make('email')->email(),
                                Select::make('type')
                                    ->options([
                                        'customer' => 'Customer',
                                        'vendor' => 'Vendor',
                                    ])
                                    ->default('customer'),
                            ]),

                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'sent' => 'Sent',
                                'partial' => 'Partially Paid',
                                'paid' => 'Paid',
                                'overdue' => 'Overdue',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft')
                            ->disabled(fn ($record) => $record && !$record->isEditable()),

                        DatePicker::make('issue_date')
                            ->label('Issue Date')
                            ->required()
                            ->default(now()),

                        DatePicker::make('due_date')
                            ->label('Due Date'),

                        Select::make('currency_code')
                            ->relationship('currency', 'code')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default('KES'),

                        TextInput::make('order_number')
                            ->label('PO / Reference Number')
                            ->maxLength(50),
                    ])
                    ->columns(2),

                Section::make('Line Items')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Select::make('account_id')
                                    ->label('Account')
                                    ->relationship('account', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                TextInput::make('description')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),

                                TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0.01),

                                TextInput::make('price')
                                    ->label('Unit Price')
                                    ->numeric()
                                    ->required()
                                    ->prefix('KES'),

                                TextInput::make('discount_percent')
                                    ->label('Discount %')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%'),

                                Select::make('tax_id')
                                    ->label('Tax')
                                    ->relationship('tax', 'name')
                                    ->searchable()
                                    ->preload(),
                            ])
                            ->columns(7)
                            ->defaultItems(1)
                            ->addActionLabel('Add Line Item')
                            ->reorderableWithButtons()
                            ->collapsible(),
                    ]),

                Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notes (visible to customer)')
                            ->rows(2)
                            ->maxLength(2000),

                        Textarea::make('footer')
                            ->label('Footer / Terms')
                            ->rows(2)
                            ->maxLength(2000),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'invoice' => 'success',
                        'bill' => 'warning',
                        'quote' => 'info',
                        'credit_note' => 'danger',
                        'debit_note' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('document_number')
                    ->label('Number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('contact.name')
                    ->label('Contact')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->due_date && $record->due_date < now() && $record->status !== 'paid' ? 'danger' : null),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->getStateUsing(fn ($record) => $record->getTotal()->format())
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'info',
                        'partial' => 'warning',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'invoice' => 'Invoice',
                        'bill' => 'Bill',
                        'quote' => 'Quote',
                        'credit_note' => 'Credit Note',
                        'debit_note' => 'Debit Note',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'partial' => 'Partially Paid',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\Filter::make('overdue')
                    ->query(fn ($query) => $query
                        ->whereIn('status', ['sent', 'partial'])
                        ->where('due_date', '<', now())
                    )
                    ->label('Overdue Only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->isEditable()),
                Tables\Actions\Action::make('send')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->action(fn ($record) => $record->markAsSent()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('issue_date', 'desc');
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
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'view' => Pages\ViewDocument::route('/{record}'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['sent', 'partial', 'overdue'])->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $overdue = static::getModel()::where('status', 'overdue')->count();
        return $overdue > 0 ? 'danger' : 'primary';
    }
}
