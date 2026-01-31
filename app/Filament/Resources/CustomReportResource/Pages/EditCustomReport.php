<?php

namespace App\Filament\Resources\CustomReportResource\Pages;

use App\Filament\Resources\CustomReportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomReport extends EditRecord
{
    protected static string $resource = CustomReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
