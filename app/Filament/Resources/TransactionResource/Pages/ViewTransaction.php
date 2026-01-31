<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use App\Services\TransactionService;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => $this->record->canEdit()),

            Action::make('post')
                ->label('Post')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->canPost())
                ->action(function (TransactionService $service) {
                    $service->post($this->record);
                    
                    Notification::make()
                        ->title('Transaction posted')
                        ->success()
                        ->send();
                    
                    $this->refreshFormData(['is_posted', 'posted_at']);
                }),

            Action::make('duplicate')
                ->label('Duplicate')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->action(function () {
                    $newTransaction = $this->record->replicate(['is_posted', 'posted_at', 'is_void', 'voided_at', 'reversed_by_id']);
                    $newTransaction->transaction_date = now();
                    $newTransaction->description = 'Copy of: ' . $this->record->description;
                    $newTransaction->save();

                    foreach ($this->record->splits as $split) {
                        $newSplit = $split->replicate(['reconciled_state', 'reconcile_date']);
                        $newSplit->transaction_id = $newTransaction->id;
                        $newSplit->reconciled_state = \App\Models\Split::RECONCILED_NOT;
                        $newSplit->save();
                    }

                    Notification::make()
                        ->title('Transaction duplicated')
                        ->success()
                        ->send();

                    return redirect()->route('filament.admin.resources.transactions.edit', $newTransaction);
                }),

            DeleteAction::make()
                ->visible(fn () => $this->record->canDelete()),
        ];
    }
}
