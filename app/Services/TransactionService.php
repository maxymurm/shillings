<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Split;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransactionService
{
    /**
     * Create a new transaction with splits.
     *
     * @param  array  $transactionData  Transaction header data
     * @param  array  $splitsData  Array of split data [{account_id, amount, action, memo?}]
     * @return Transaction
     *
     * @throws RuntimeException If transaction doesn't balance
     */
    public function create(array $transactionData, array $splitsData): Transaction
    {
        // Validate that splits balance
        $this->validateSplitsBalance($splitsData);

        return DB::transaction(function () use ($transactionData, $splitsData) {
            // Create the transaction
            $transaction = Transaction::create([
                'company_id' => $transactionData['company_id'],
                'transaction_date' => $transactionData['transaction_date'] ?? now()->toDateString(),
                'description' => $transactionData['description'] ?? null,
                'num' => $transactionData['num'] ?? null,
                'notes' => $transactionData['notes'] ?? null,
                'is_posted' => false,
                'created_by' => $transactionData['created_by'] ?? auth()->id(),
            ]);

            // Create splits
            foreach ($splitsData as $splitData) {
                $this->createSplit($transaction, $splitData);
            }

            return $transaction->load('splits.account');
        });
    }

    /**
     * Update an existing transaction.
     *
     * @param  Transaction  $transaction  The transaction to update
     * @param  array  $transactionData  Updated transaction data
     * @param  array|null  $splitsData  Updated splits (null to keep existing)
     * @return Transaction
     *
     * @throws RuntimeException If transaction is posted or doesn't balance
     */
    public function update(Transaction $transaction, array $transactionData, ?array $splitsData = null): Transaction
    {
        if ($transaction->is_posted) {
            throw new RuntimeException('Cannot modify a posted transaction. Create a reversing entry instead.');
        }

        if ($splitsData !== null) {
            $this->validateSplitsBalance($splitsData);
        }

        return DB::transaction(function () use ($transaction, $transactionData, $splitsData) {
            // Update transaction header
            $transaction->update([
                'transaction_date' => $transactionData['transaction_date'] ?? $transaction->transaction_date,
                'description' => $transactionData['description'] ?? $transaction->description,
                'num' => $transactionData['num'] ?? $transaction->num,
                'notes' => $transactionData['notes'] ?? $transaction->notes,
            ]);

            // Update splits if provided
            if ($splitsData !== null) {
                // Delete existing splits
                $transaction->splits()->delete();

                // Create new splits
                foreach ($splitsData as $splitData) {
                    $this->createSplit($transaction, $splitData);
                }
            }

            return $transaction->fresh(['splits.account']);
        });
    }

    /**
     * Post a transaction (mark as immutable).
     *
     * @param  Transaction  $transaction  The transaction to post
     * @return Transaction
     *
     * @throws RuntimeException If transaction doesn't balance
     */
    public function post(Transaction $transaction): Transaction
    {
        if ($transaction->is_posted) {
            return $transaction;
        }

        if (! $this->validate($transaction)) {
            throw new RuntimeException('Cannot post an unbalanced transaction');
        }

        $transaction->is_posted = true;
        $transaction->post_date = now();
        $transaction->save();

        return $transaction;
    }

    /**
     * Create a reversing entry for a transaction.
     *
     * @param  Transaction  $transaction  The transaction to reverse
     * @param  string|null  $description  Description for reversal
     * @param  Carbon|string|null  $date  Date for reversal (default: today)
     * @return Transaction
     */
    public function reverse(
        Transaction $transaction,
        ?string $description = null,
        Carbon|string|null $date = null
    ): Transaction {
        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($transaction, $description, $date) {
            // Create reversal transaction
            $reversal = Transaction::create([
                'company_id' => $transaction->company_id,
                'transaction_date' => $date,
                'description' => $description ?? "Reversal of: {$transaction->description}",
                'num' => $transaction->num ? "REV-{$transaction->num}" : null,
                'notes' => "Reversal of transaction ID: {$transaction->id}",
                'is_posted' => false,
                'created_by' => auth()->id(),
            ]);

            // Create reversed splits (swap debit/credit)
            foreach ($transaction->splits as $split) {
                Split::create([
                    'transaction_id' => $reversal->id,
                    'account_id' => $split->account_id,
                    'amount_num' => $split->amount_num,
                    'amount_denom' => $split->amount_denom,
                    'value_num' => $split->value_num,
                    'value_denom' => $split->value_denom,
                    'action' => $split->action === Split::DEBIT ? Split::CREDIT : Split::DEBIT,
                    'memo' => "Reversal: {$split->memo}",
                ]);
            }

            return $reversal->load('splits.account');
        });
    }

    /**
     * Validate that a transaction balances (debits = credits).
     *
     * @param  Transaction  $transaction  The transaction to validate
     * @return bool
     */
    public function validate(Transaction $transaction): bool
    {
        $debits = $transaction->splits()
            ->where('action', Split::DEBIT)
            ->sum('value_num');

        $credits = $transaction->splits()
            ->where('action', Split::CREDIT)
            ->sum('value_num');

        return $debits === $credits;
    }

    /**
     * Create a simple two-split transaction (most common case).
     *
     * @param  string  $companyId  Company ID
     * @param  Account  $debitAccount  Account to debit
     * @param  Account  $creditAccount  Account to credit
     * @param  float  $amount  Amount
     * @param  string  $description  Transaction description
     * @param  Carbon|string|null  $date  Transaction date
     * @return Transaction
     */
    public function createSimple(
        string $companyId,
        Account $debitAccount,
        Account $creditAccount,
        float $amount,
        string $description,
        Carbon|string|null $date = null
    ): Transaction {
        $transactionData = [
            'company_id' => $companyId,
            'transaction_date' => $date ? Carbon::parse($date)->toDateString() : now()->toDateString(),
            'description' => $description,
        ];

        $splitsData = [
            [
                'account_id' => $debitAccount->id,
                'amount' => $amount,
                'action' => Split::DEBIT,
            ],
            [
                'account_id' => $creditAccount->id,
                'amount' => $amount,
                'action' => Split::CREDIT,
            ],
        ];

        return $this->create($transactionData, $splitsData);
    }

    /**
     * Create a split for a transaction.
     */
    protected function createSplit(Transaction $transaction, array $splitData): Split
    {
        $amount = $splitData['amount'];
        $denominator = 100;
        $numerator = (int) round($amount * $denominator);

        return Split::create([
            'transaction_id' => $transaction->id,
            'account_id' => $splitData['account_id'],
            'amount_num' => $numerator,
            'amount_denom' => $denominator,
            'value_num' => $numerator,
            'value_denom' => $denominator,
            'action' => $splitData['action'],
            'memo' => $splitData['memo'] ?? null,
        ]);
    }

    /**
     * Validate that splits balance (debits = credits).
     *
     * @throws RuntimeException If splits don't balance
     */
    protected function validateSplitsBalance(array $splitsData): void
    {
        $debits = 0;
        $credits = 0;

        foreach ($splitsData as $split) {
            if ($split['action'] === Split::DEBIT) {
                $debits += $split['amount'];
            } else {
                $credits += $split['amount'];
            }
        }

        // Allow for small floating point differences
        if (abs($debits - $credits) > 0.001) {
            throw new RuntimeException(
                "Transaction does not balance. Debits: {$debits}, Credits: {$credits}"
            );
        }
    }

    /**
     * Void a transaction (create void splits instead of deleting).
     *
     * @param  Transaction  $transaction  The transaction to void
     * @param  string|null  $reason  Reason for voiding
     * @return Transaction
     */
    public function void(Transaction $transaction, ?string $reason = null): Transaction
    {
        return DB::transaction(function () use ($transaction, $reason) {
            // Mark all splits as void
            $transaction->splits()->update([
                'reconciled_state' => Split::RECONCILED_VOID,
                'memo' => DB::raw("CONCAT(COALESCE(memo, ''), ' [VOIDED: " . ($reason ?? 'No reason') . "]')"),
            ]);

            // Update transaction notes
            $transaction->notes = ($transaction->notes ?? '') . "\n[VOIDED: " . ($reason ?? 'No reason') . ']';
            $transaction->save();

            return $transaction;
        });
    }

    /**
     * Get transaction summary for a company.
     *
     * @param  string  $companyId  Company ID
     * @param  Carbon|string|null  $startDate  Start date
     * @param  Carbon|string|null  $endDate  End date
     * @return array{total_count: int, posted_count: int, draft_count: int, total_amount: float}
     */
    public function getTransactionSummary(
        string $companyId,
        Carbon|string|null $startDate = null,
        Carbon|string|null $endDate = null
    ): array {
        $query = Transaction::query()
            ->where('company_id', $companyId);

        if ($startDate) {
            $query->where('transaction_date', '>=', Carbon::parse($startDate));
        }

        if ($endDate) {
            $query->where('transaction_date', '<=', Carbon::parse($endDate));
        }

        $transactions = $query->get();

        $totalAmount = 0;
        foreach ($transactions as $transaction) {
            $totalAmount += $transaction->splits()
                ->where('action', Split::DEBIT)
                ->sum('value_num') / 100;
        }

        return [
            'total_count' => $transactions->count(),
            'posted_count' => $transactions->where('is_posted', true)->count(),
            'draft_count' => $transactions->where('is_posted', false)->count(),
            'total_amount' => $totalAmount,
        ];
    }
}
