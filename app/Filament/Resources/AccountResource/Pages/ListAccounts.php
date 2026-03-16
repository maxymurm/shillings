<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Models\Company;
use App\Services\GnuCashImportService;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListAccounts extends ListRecords
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('importGnuCash')
                ->label('Import from GnuCash')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->modalHeading('Import GnuCash Chart of Accounts')
                ->modalDescription(
                    'Upload a CSV file exported from GnuCash (File → Export → Export Accounts). ' .
                    'Accounts will be imported into the current company and placed into the matching hierarchy.'
                )
                ->modalWidth('lg')
                ->form([
                    FileUpload::make('file')
                        ->label('GnuCash Accounts CSV')
                        ->disk('local')
                        ->directory('gnucash-tmp')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/csv'])
                        ->maxSize(4096)
                        ->required()
                        ->helperText('In GnuCash: File → Export → Export Accounts → save as CSV'),
                    Toggle::make('skip_existing')
                        ->label('Skip accounts whose code already exists in this company')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    $companyId = session('active_company_id') ?? Company::first()?->id;
                    $company   = Company::find($companyId);

                    if (! $company) {
                        Notification::make()->title('No active company found.')->danger()->send();
                        return;
                    }

                    $content = Storage::disk('local')->get($data['file']);
                    Storage::disk('local')->delete($data['file']);

                    if (! $content) {
                        Notification::make()->title('Could not read the uploaded file.')->danger()->send();
                        return;
                    }

                    $result = app(GnuCashImportService::class)->import(
                        $content,
                        $company,
                        $data['skip_existing'] ?? true
                    );

                    $body = "Created: {$result['created']} · Skipped: {$result['skipped']}";

                    if (! empty($result['errors'])) {
                        $body .= ' · ' . count($result['errors']) . ' error(s): ' .
                                 implode('; ', array_slice($result['errors'], 0, 3));
                    }

                    if ($result['created'] > 0) {
                        Notification::make()
                            ->title('Import complete')
                            ->body($body)
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Import finished — no new accounts created')
                            ->body($body)
                            ->warning()
                            ->send();
                    }
                }),

            Actions\CreateAction::make(),
        ];
    }
}
