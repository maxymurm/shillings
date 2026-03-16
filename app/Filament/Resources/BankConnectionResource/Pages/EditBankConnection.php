<?php

namespace App\Filament\Resources\BankConnectionResource\Pages;

use App\Filament\Resources\BankConnectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBankConnection extends EditRecord
{
    protected static string $resource = BankConnectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
