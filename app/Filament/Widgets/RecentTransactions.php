<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentTransactions extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Recent Transactions';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->whereHas('splits.account', fn (Builder $q) => $q->where('company_id', session('active_company_id')))
                    ->latest('date')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('num')
                    ->label('Ref #')
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->getStateUsing(function (Transaction $record): string {
                        $split = $record->splits->first();
                        if (! $split) {
                            return '-';
                        }

                        $amount = $split->amount_num / $split->amount_denom;

                        return number_format(abs($amount), 2);
                    })
                    ->alignEnd(),

                Tables\Columns\IconColumn::make('is_posted')
                    ->label('Posted')
                    ->boolean()
                    ->getStateUsing(fn (Transaction $record) => ! is_null($record->posted_at)),

                Tables\Columns\IconColumn::make('is_void')
                    ->label('Void')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedXCircle)
                    ->falseIcon(Heroicon::OutlinedCheckCircle)
                    ->trueColor('danger')
                    ->falseColor('success'),
            ])
            ->paginated(false);
    }
}
