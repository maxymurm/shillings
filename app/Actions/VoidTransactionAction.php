<?php

namespace App\Actions;

use App\Models\Transaction;
use App\Services\AccountBalanceService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Void Transaction Action
 *
 * Voids a transaction without creating a reversal.
 * Sets all split amounts to zero, preserving the audit trail.
 */
class VoidTransactionAction
{
    public function __construct(
        private readonly AccountBalanceService $balanceService
    ) {}

    /**
     * Void a transaction.
     *
     * @throws InvalidArgumentException
     */
    public function execute(Transaction $transaction, string $reason): Transaction
    {
        // Can only void posted transactions
        if (! $transaction->is_posted) {
            throw new InvalidArgumentException('Only posted transactions can be voided. Delete draft transactions instead.');
        }

        // Check if already voided
        if ($transaction->is_void) {
            throw new InvalidArgumentException('Transaction has already been voided.');
        }

        // Check if already reversed
        if ($transaction->reversed_by_id) {
            throw new InvalidArgumentException('Cannot void a reversed transaction.');
        }

        return DB::transaction(function () use ($transaction, $reason) {
            // Mark as void
            $transaction->is_void = true;
            $transaction->void_reason = $reason;
            $transaction->voided_at = now();
            $transaction->voided_by_id = auth()->id();
            $transaction->save();

            // Zero out all splits while preserving original memo
            foreach ($transaction->splits as $split) {
                $originalAmount = $split->amount_num / $split->amount_denom;
                $split->memo = "[VOID: {$originalAmount}] {$split->memo}";
                $split->amount_num = 0;
                $split->save();
            }

            // Invalidate account balances (will be recalculated)
            $this->balanceService->onTransactionInvalidated($transaction);

            return $transaction->fresh(['splits.account']);
        });
    }
}
