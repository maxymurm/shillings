<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Models\Contact;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\Toggle;
use Filament\Schemas\Components\Repeater;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Contacts';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contact Details')
                    ->schema([
                        Select::make('type')
                            ->options([
                                'customer' => 'Customer',
                                'vendor' => 'Vendor',
                                'employee' => 'Employee',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->default('customer'),

                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->email()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),

                        TextInput::make('website')
                            ->url()
                            ->maxLength(255),

                        TextInput::make('tax_number')
                            ->label('Tax Number / PIN')
                            ->maxLength(50),

                        Select::make('currency_code')
                            ->relationship('currency', 'code')
                            ->searchable()
                            ->preload(),

                        Toggle::make('enabled')
                            ->default(true)
                            ->helperText('Disabled contacts will not appear in dropdowns'),
                    ])
                    ->columns(2),

                Section::make('Address')
                    ->schema([
                        TextInput::make('address_line1')
                            ->label('Address Line 1')
                            ->maxLength(255),

                        TextInput::make('address_line2')
                            ->label('Address Line 2')
                            ->maxLength(255),

                        TextInput::make('city')
                            ->maxLength(100),

                        TextInput::make('state')
                            ->label('State / Province')
                            ->maxLength(100),

                        TextInput::make('zip_code')
                            ->label('ZIP / Postal Code')
                            ->maxLength(20),

                        TextInput::make('country')
                            ->maxLength(100)
                            ->default('Kenya'),
                    ])
                    ->columns(2),

                Section::make('Contact Persons')
                    ->schema([
                        Repeater::make('persons')
                            ->relationship()
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('email')
                                    ->email()
                                    ->maxLength(255),

                                TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(50),

                                TextInput::make('position')
                                    ->maxLength(100),

                                Toggle::make('is_primary')
                                    ->label('Primary Contact')
                                    ->default(false),
                            ])
                            ->columns(5)
                            ->defaultItems(0)
                            ->addActionLabel('Add Contact Person'),
                    ])
                    ->collapsible(),

                Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->rows(3)
                            ->maxLength(2000),
                    ])
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
                        'customer' => 'success',
                        'vendor' => 'warning',
                        'employee' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('tax_number')
                    ->label('Tax Number')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('city')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('enabled')
                    ->boolean(),

                Tables\Columns\TextColumn::make('documents_count')
                    ->counts('documents')
                    ->label('Documents'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'customer' => 'Customer',
                        'vendor' => 'Vendor',
                        'employee' => 'Employee',
                        'other' => 'Other',
                    ]),

                Tables\Filters\TernaryFilter::make('enabled')
                    ->label('Active')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->placeholder('All'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
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
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'view' => Pages\ViewContact::route('/{record}'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('enabled', true)->count();
    }
}
