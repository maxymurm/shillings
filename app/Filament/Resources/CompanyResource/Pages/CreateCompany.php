<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use App\Models\Company;
use App\Services\ChartOfAccountsService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;

    protected ?string $chartOfAccountsTemplate = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extract the virtual template field — not a DB column
        if (isset($data['chart_of_accounts_template'])) {
            $this->chartOfAccountsTemplate = $data['chart_of_accounts_template'];
            unset($data['chart_of_accounts_template']);
        }

        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }

    protected function afterCreate(): void
    {
        // Attach the creating user as owner
        $this->record->users()->attach(auth()->id(), ['id' => (string) \Illuminate\Support\Str::uuid(), 'role' => 'owner']);

        // Set as active company
        session(['active_company_id' => $this->record->id]);

        // Import chart of accounts template if one was selected
        if ($this->chartOfAccountsTemplate && $this->chartOfAccountsTemplate !== 'none') {
            try {
                $result = app(ChartOfAccountsService::class)
                    ->importTemplate($this->record, $this->chartOfAccountsTemplate);

                Notification::make()
                    ->title('Chart of accounts imported')
                    ->body("Imported {$result['accounts_created']} accounts from the {$result['template']} template.")
                    ->success()
                    ->send();
            } catch (\Throwable $e) {
                Notification::make()
                    ->title('Template import failed')
                    ->body('Organisation created, but the starter template could not be loaded. You can import accounts manually.')
                    ->warning()
                    ->send();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
