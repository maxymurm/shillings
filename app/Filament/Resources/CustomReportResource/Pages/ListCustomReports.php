<?php

namespace App\Filament\Resources\CustomReportResource\Pages;

use App\Filament\Resources\CustomReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomReports extends ListRecords
{
    protected static string $resource = CustomReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
