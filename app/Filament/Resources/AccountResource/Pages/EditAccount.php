<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    // Check constraints before delete
                    if ($this->record->children()->exists()) {
                        throw new \Exception('Cannot delete account with children.');
                    }
                    if ($this->record->splits()->exists()) {
                        throw new \Exception('Cannot delete account with transactions.');
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Recalculate depth on parent change
        if (isset($data['parent_id'])) {
            $data['depth'] = $this->record->calculateDepth($data['parent_id']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
