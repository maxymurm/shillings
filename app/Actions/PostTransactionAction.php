<?php

namespace App\Actions;

use App\Models\Transaction;
use App\Services\AccountBalanceService;
use App\Validators\SplitValidator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Post Transaction Action
 *
 * Posts a transaction, making it immutable and affecting account balances.
 * Posted transactions cannot be edited, only reversed.
 */
class PostTransactionAction
{
    public function __construct(
        private readonly SplitValidator $validator,
        private readonly AccountBalanceService $balanceService
    ) {}

    /**
     * Post a transaction.
     *
     * @throws InvalidArgumentException
     */
    public function execute(Transaction $transaction): Transaction
    {
        // Check if already posted
        if ($transaction->is_posted) {
            throw new InvalidArgumentException('Transaction is already posted.');
        }

        // Validate splits balance
        if (! $this->validator->validate($transaction)) {
            $errors = $this->validator->getErrors();
            $message = $this->validator->getErrorMessage() ?? 'Transaction validation failed.';

            throw new InvalidArgumentException($message . ' ' . json_encode($errors));
        }

        return DB::transaction(function () use ($transaction) {
            // Mark as posted
            $transaction->is_posted = true;
            $transaction->posted_at = now();
            $transaction->save();

            // Update account balances
            $this->updateAccountBalances($transaction);

            // Fire event for audit trail
            // event(new TransactionPosted($transaction));

            return $transaction->fresh(['splits.account']);
        });
    }

    /**
     * Update cached account balances after posting.
     */
    private function updateAccountBalances(Transaction $transaction): void
    {
        $this->balanceService->onTransactionPosted($transaction);
    }
}
