<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => $this->record->isEditable()),

            Actions\Action::make('send')
                ->label('Send')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn () => $this->record->status === 'draft')
                ->action(fn () => $this->record->markAsSent())
                ->after(fn () => $this->refreshFormData(['status'])),

            Actions\Action::make('duplicate')
                ->label('Duplicate')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->action(function () {
                    $documentService = app(\App\Services\DocumentService::class);
                    $newDocument = $documentService->duplicate($this->record);
                    
                    return redirect()->to(DocumentResource::getUrl('edit', ['record' => $newDocument]));
                }),

            Actions\Action::make('recordPayment')
                ->label('Record Payment')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, ['sent', 'partial', 'overdue']))
                ->form([
                    \Filament\Schemas\Components\TextInput::make('amount')
                        ->label('Amount')
                        ->numeric()
                        ->required()
                        ->prefix('KES'),

                    \Filament\Schemas\Components\Select::make('payment_account_id')
                        ->label('Payment Account')
                        ->relationship('company.accounts', 'name', fn ($query) => $query
                            ->whereHas('accountType', fn ($q) => $q->where('type', 'asset'))
                        )
                        ->required()
                        ->searchable()
                        ->preload(),

                    \Filament\Schemas\Components\DatePicker::make('payment_date')
                        ->label('Payment Date')
                        ->default(now()),

                    \Filament\Schemas\Components\TextInput::make('reference')
                        ->label('Reference')
                        ->maxLength(100),
                ])
                ->action(function (array $data) {
                    $documentService = app(\App\Services\DocumentService::class);
                    $amount = \App\ValueObjects\Money::fromFloat($data['amount'], $this->record->currency_code);
                    $paymentAccount = \App\Models\Account::find($data['payment_account_id']);

                    $documentService->recordPayment(
                        $this->record,
                        $amount,
                        $paymentAccount,
                        $data['payment_date'] ? \Carbon\Carbon::parse($data['payment_date']) : null,
                        $data['reference'] ?? null
                    );

                    $this->refreshFormData(['status', 'paid_amount_num', 'paid_amount_denom']);
                }),
        ];
    }
}
