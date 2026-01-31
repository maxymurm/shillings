<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 120;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('password')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(255)
                            ->helperText(fn (string $operation): string => $operation === 'edit'
                                ? 'Leave blank to keep current password'
                                : ''),
                    ])
                    ->columns(2),

                Section::make('Company Role')
                    ->schema([
                        Select::make('company_role')
                            ->label('Role')
                            ->options([
                                'owner' => 'Owner',
                                'admin' => 'Admin',
                                'accountant' => 'Accountant',
                                'bookkeeper' => 'Bookkeeper',
                                'viewer' => 'Viewer',
                            ])
                            ->required()
                            ->helperText('Role within the current company'),
                    ])
                    ->visible(fn (string $operation): bool => $operation === 'edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('pivot.role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'owner' => 'success',
                        'admin' => 'warning',
                        'accountant' => 'info',
                        'bookkeeper' => 'primary',
                        'viewer' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('Verified')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'owner' => 'Owner',
                        'admin' => 'Admin',
                        'accountant' => 'Accountant',
                        'bookkeeper' => 'Bookkeeper',
                        'viewer' => 'Viewer',
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn ($q, $role) => $q->wherePivot('role', $role)
                    )),
            ])
            ->actions([
                Tables\Actions\Action::make('changeRole')
                    ->label('Change Role')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('warning')
                    ->form([
                        Select::make('role')
                            ->options([
                                'owner' => 'Owner',
                                'admin' => 'Admin',
                                'accountant' => 'Accountant',
                                'bookkeeper' => 'Bookkeeper',
                                'viewer' => 'Viewer',
                            ])
                            ->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        $companyId = session('active_company_id');
                        $record->companies()
                            ->updateExistingPivot($companyId, ['role' => $data['role']]);
                    }),
                Tables\Actions\Action::make('removeFromCompany')
                    ->label('Remove')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $companyId = session('active_company_id');
                        $record->companies()->detach($companyId);
                    })
                    ->visible(fn (User $record) => $record->id !== auth()->id()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('removeAll')
                        ->label('Remove Selected')
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $companyId = session('active_company_id');
                            foreach ($records as $record) {
                                if ($record->id !== auth()->id()) {
                                    $record->companies()->detach($companyId);
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('invite')
                    ->label('Invite User')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->form([
                        TextInput::make('email')
                            ->email()
                            ->required(),
                        Select::make('role')
                            ->options([
                                'admin' => 'Admin',
                                'accountant' => 'Accountant',
                                'bookkeeper' => 'Bookkeeper',
                                'viewer' => 'Viewer',
                            ])
                            ->default('viewer')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $companyId = session('active_company_id');

                        $user = User::where('email', $data['email'])->first();

                        if ($user) {
                            if ($user->companies()->where('company_id', $companyId)->exists()) {
                                throw new \Exception('User is already a member of this company.');
                            }

                            $user->companies()->attach($companyId, ['role' => $data['role']]);
                        } else {
                            throw new \Exception('User not found. Invitation emails not yet implemented.');
                        }
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $companyId = session('active_company_id');

        return parent::getEloquentQuery()
            ->when($companyId, function ($query) use ($companyId) {
                $query->whereHas('companies', fn ($q) => $q->where('company_id', $companyId));
            });
    }
}
