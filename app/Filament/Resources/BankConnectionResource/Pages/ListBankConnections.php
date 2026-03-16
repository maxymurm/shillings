<?php

namespace App\Filament\Resources\BankConnectionResource\Pages;

use App\Filament\Resources\BankConnectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBankConnections extends ListRecords
{
    protected static string $resource = BankConnectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
