<?php

namespace App\Filament\Resources\ScheduledTransactionResource\Pages;

use App\Filament\Resources\ScheduledTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScheduledTransaction extends EditRecord
{
    protected static string $resource = ScheduledTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
