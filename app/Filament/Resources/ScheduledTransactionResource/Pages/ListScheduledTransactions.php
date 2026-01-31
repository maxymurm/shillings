<?php

namespace App\Filament\Resources\ScheduledTransactionResource\Pages;

use App\Filament\Resources\ScheduledTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScheduledTransactions extends ListRecords
{
    protected static string $resource = ScheduledTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
