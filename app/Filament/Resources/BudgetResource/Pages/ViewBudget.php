<?php

namespace App\Filament\Resources\BudgetResource\Pages;

use App\Filament\Resources\BudgetResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBudget extends ViewRecord
{
    protected static string $resource = BudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('clone')
                ->label('Clone to New Year')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->form([
                    \Filament\Schemas\Components\TextInput::make('new_year')
                        ->label('New Fiscal Year')
                        ->required()
                        ->numeric()
                        ->default(fn () => $this->record->fiscal_year + 1),
                ])
                ->action(function (array $data) {
                    $budgetService = app(\App\Services\BudgetService::class);
                    $newBudget = $budgetService->cloneForNewYear($this->record, $data['new_year']);
                    
                    return redirect()->to(BudgetResource::getUrl('edit', ['record' => $newBudget]));
                }),
        ];
    }
}
