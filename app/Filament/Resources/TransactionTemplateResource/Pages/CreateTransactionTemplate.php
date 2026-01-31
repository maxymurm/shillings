<?php

namespace App\Filament\Resources\TransactionTemplateResource\Pages;

use App\Filament\Resources\TransactionTemplateResource;
use App\Models\Company;
use Filament\Resources\Pages\CreateRecord;

class CreateTransactionTemplate extends CreateRecord
{
    protected static string $resource = TransactionTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = session('current_company_id') ?? Company::first()?->id;
        $data['created_by_id'] = auth()->id();

        return $data;
    }
}
