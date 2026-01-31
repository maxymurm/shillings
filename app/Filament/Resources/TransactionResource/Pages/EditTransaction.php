<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use App\Services\TransactionService;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('post')
                ->label('Post Transaction')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Post Transaction')
                ->modalDescription('Once posted, this transaction cannot be edited. Are you sure?')
                ->visible(fn () => $this->record->canPost())
                ->action(function (TransactionService $service) {
                    $service->post($this->record);
                    
                    Notification::make()
                        ->title('Transaction posted')
                        ->success()
                        ->send();
                    
                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            DeleteAction::make()
                ->visible(fn () => $this->record->canDelete()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Transaction updated');
    }

    protected function beforeSave(): void
    {
        // Validate the transaction can be edited
        if ($this->record->is_posted) {
            Notification::make()
                ->danger()
                ->title('Cannot edit posted transaction')
                ->body('Create a reversing entry instead.')
                ->send();
            
            $this->halt();
        }
    }
}
