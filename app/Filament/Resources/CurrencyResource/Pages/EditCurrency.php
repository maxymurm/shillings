<?php

namespace App\Filament\Resources\CurrencyResource\Pages;

use App\Filament\Resources\CurrencyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCurrency extends EditRecord
{
    protected static string $resource = CurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    // Check if currency is in use
                    if ($this->record->accounts()->exists()) {
                        throw new \Exception('Cannot delete currency that is in use by accounts.');
                    }
                    if ($this->record->companiesUsingAsDefault()->exists()) {
                        throw new \Exception('Cannot delete currency that is set as default for a company.');
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
