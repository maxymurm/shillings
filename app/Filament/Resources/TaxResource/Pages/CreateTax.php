<?php

namespace App\Filament\Resources\TaxResource\Pages;

use App\Filament\Resources\TaxResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTax extends CreateRecord
{
    protected static string $resource = TaxResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = session('active_company_id');
        
        // Convert rate to GnuCash precision
        if (isset($data['rate'])) {
            $data['rate_num'] = (int) ($data['rate'] * 100);
            $data['rate_denom'] = 10000;
            unset($data['rate']);
        }
        
        return $data;
    }
}
