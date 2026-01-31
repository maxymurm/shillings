<?php

namespace App\Filament\Resources\TransactionTemplateResource\Pages;

use App\Filament\Resources\TransactionTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTransactionTemplates extends ListRecords
{
    protected static string $resource = TransactionTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
