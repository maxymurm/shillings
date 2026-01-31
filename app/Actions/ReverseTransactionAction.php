<?php

namespace App\Actions;

use App\Models\Transaction;
use App\Services\AccountBalanceService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Reverse Transaction Action
 *
 * Creates a reversing entry for a posted transaction.
 * The original transaction remains unchanged but is linked to its reversal.
 */
class ReverseTransactionAction
{
    public function __construct(
        private readonly AccountBalanceService $balanceService
    ) {}

    /**
     * Reverse a posted transaction.
     *
     * @throws InvalidArgumentException
     */
    public function execute(Transaction $transaction, string $reason = 'Transaction reversal'): Transaction
    {
        // Can only reverse posted transactions
        if (! $transaction->is_posted) {
            throw new InvalidArgumentException('Only posted transactions can be reversed.');
        }

        // Check if already reversed
        if ($transaction->reversed_by_id) {
            throw new InvalidArgumentException('Transaction has already been reversed.');
        }

        return DB::transaction(function () use ($transaction, $reason) {
            // Create reversal transaction
            $reversal = Transaction::create([
                'company_id' => $transaction->company_id,
                'currency_id' => $transaction->currency_id,
                'description' => "Reversal: {$transaction->description}",
                'reference' => $transaction->reference ? "REV-{$transaction->reference}" : null,
                'post_date' => now()->toDateString(),
                'notes' => $reason,
                'is_posted' => true,
                'posted_at' => now(),
                'created_by_id' => auth()->id(),
                'reverses_id' => $transaction->id,
            ]);

            // Create reversed splits (same amounts, but negated)
            foreach ($transaction->splits as $split) {
                $reversal->splits()->create([
                    'account_id' => $split->account_id,
                    'amount_num' => -$split->amount_num,
                    'amount_denom' => $split->amount_denom,
                    'value_num' => $split->value_num ? -$split->value_num : null,
                    'value_denom' => $split->value_denom,
                    'memo' => "Reversal: {$split->memo}",
                    'reconciled_state' => 'n', // New splits start unreconciled
                ]);
            }

            // Mark original as reversed
            $transaction->reversed_by_id = $reversal->id;
            $transaction->save();

            // Update account balances for the reversal transaction
            $this->balanceService->onTransactionPosted($reversal);

            return $reversal->fresh(['splits.account']);
        });
    }
}
