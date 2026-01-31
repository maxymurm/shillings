<?php

namespace App\Filament\Resources\ScheduledTransactionResource\Pages;

use App\Filament\Resources\ScheduledTransactionResource;
use App\Models\Company;
use Filament\Resources\Pages\CreateRecord;

class CreateScheduledTransaction extends CreateRecord
{
    protected static string $resource = ScheduledTransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = session('current_company_id') ?? Company::first()?->id;
        $data['created_by_id'] = auth()->id();
        
        // Set next_occurrence from start_date if not set
        if (empty($data['next_occurrence']) && !empty($data['start_date'])) {
            $data['next_occurrence'] = $data['start_date'];
        }

        return $data;
    }
}
