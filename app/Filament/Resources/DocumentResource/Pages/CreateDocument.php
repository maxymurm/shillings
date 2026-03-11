<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use App\Services\DocumentService;
use Filament\Resources\Pages\CreateRecord;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = session('active_company_id');
        
        // Generate document number if not provided
        if (empty($data['document_number'])) {
            $documentService = app(DocumentService::class);
            $data['document_number'] = $documentService->generateDocumentNumber(
                $data['company_id'],
                $data['type']
            );
        }
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Calculate totals after items are saved
        $this->record->calculateTotals();
    }
}
