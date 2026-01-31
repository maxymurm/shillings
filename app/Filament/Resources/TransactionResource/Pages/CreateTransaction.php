<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use App\Models\Company;
use App\Models\Currency;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set company_id from session or default
        $data['company_id'] = session('current_company_id') ?? Company::first()?->id;
        
        // Set currency_id if not set
        if (empty($data['currency_id'])) {
            $data['currency_id'] = Currency::where('code', 'KES')->first()?->id;
        }
        
        // Set created_by
        $data['created_by_id'] = auth()->id();
        
        return $data;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Transaction created')
            ->body('The transaction has been created as a draft. Post it when ready.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
