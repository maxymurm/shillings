<?php

namespace App\Filament\Resources\CustomReportResource\Pages;

use App\Filament\Resources\CustomReportResource;
use App\Models\Company;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomReport extends CreateRecord
{
    protected static string $resource = CustomReportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = session('current_company_id') ?? Company::first()?->id;
        $data['created_by_id'] = auth()->id();

        return $data;
    }
}
