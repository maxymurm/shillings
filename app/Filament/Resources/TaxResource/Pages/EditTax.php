<?php

namespace App\Filament\Resources\TaxResource\Pages;

use App\Filament\Resources\TaxResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTax extends EditRecord
{
    protected static string $resource = TaxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Convert from GnuCash precision to display
        if (isset($data['rate_num'], $data['rate_denom'])) {
            $data['rate'] = $data['rate_num'] / $data['rate_denom'] * 100;
        }
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Convert rate to GnuCash precision
        if (isset($data['rate'])) {
            $data['rate_num'] = (int) ($data['rate'] * 100);
            $data['rate_denom'] = 10000;
            unset($data['rate']);
        }
        
        return $data;
    }
}
