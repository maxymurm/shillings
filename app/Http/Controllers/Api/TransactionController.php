<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    /**
     * List all transactions for the current company.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::query()
            ->with(['splits.account', 'currency', 'createdBy']);

        // Filter by account
        if ($request->has('account_id')) {
            $query->whereHas('splits', fn ($q) => $q->where('account_id', $request->account_id));
        }

        // Filter by posted status
        if ($request->has('is_posted')) {
            $query->where('is_posted', filter_var($request->is_posted, FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('post_date', '>=', $request->date('start_date'));
        }
        if ($request->has('end_date')) {
            $query->where('post_date', '<=', $request->date('end_date'));
        }

        // Search by description or reference
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        $transactions = $query
            ->orderBy('post_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($transactions);
    }

    /**
     * Get a single transaction.
     */
    public function show(Transaction $transaction): JsonResponse
    {
        $transaction->load(['splits.account.accountType', 'splits.account.currency', 'currency', 'createdBy']);

        return response()->json([
            'transaction' => $transaction,
            'is_balanced' => $transaction->isBalanced(),
        ]);
    }

    /**
     * Create a new transaction with splits.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'post_date' => ['required', 'date'],
            'currency_id' => ['required', 'uuid', 'exists:currencies,id'],
            'notes' => ['nullable', 'string'],
            'splits' => ['required', 'array', 'min:2'],
            'splits.*.account_id' => ['required', 'uuid', 'exists:accounts,id'],
            'splits.*.amount_num' => ['required', 'integer'],
            'splits.*.amount_denom' => ['required', 'integer', 'min:1'],
            'splits.*.memo' => ['nullable', 'string', 'max:255'],
            'splits.*.reconciled_state' => ['nullable', 'string', 'in:n,c,y,f,v'],
        ]);

        try {
            $transaction = $this->transactionService->create($validated);
            $transaction->load(['splits.account', 'currency']);

            return response()->json([
                'message' => 'Transaction created successfully',
                'transaction' => $transaction,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'splits' => [$e->getMessage()],
            ]);
        }
    }

    /**
     * Create a simple two-account transaction.
     */
    public function storeSimple(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'post_date' => ['required', 'date'],
            'from_account_id' => ['required', 'uuid', 'exists:accounts,id'],
            'to_account_id' => ['required', 'uuid', 'exists:accounts,id', 'different:from_account_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
            'memo' => ['nullable', 'string', 'max:255'],
        ]);

        $fromAccount = Account::findOrFail($validated['from_account_id']);
        $toAccount = Account::findOrFail($validated['to_account_id']);

        try {
            $transaction = $this->transactionService->createSimple(
                fromAccount: $fromAccount,
                toAccount: $toAccount,
                amount: $validated['amount'],
                description: $validated['description'],
                postDate: $validated['post_date'],
                reference: $validated['reference'] ?? null,
                memo: $validated['memo'] ?? null
            );

            $transaction->load(['splits.account', 'currency']);

            return response()->json([
                'message' => 'Transaction created successfully',
                'transaction' => $transaction,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'amount' => [$e->getMessage()],
            ]);
        }
    }

    /**
     * Update a transaction.
     */
    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        // Cannot update posted transactions
        if ($transaction->is_posted) {
            return response()->json([
                'message' => 'Cannot update a posted transaction. Reverse it first.',
            ], 422);
        }

        $validated = $request->validate([
            'description' => ['sometimes', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'post_date' => ['sometimes', 'date'],
            'currency_id' => ['sometimes', 'uuid', 'exists:currencies,id'],
            'notes' => ['nullable', 'string'],
            'splits' => ['sometimes', 'array', 'min:2'],
            'splits.*.id' => ['nullable', 'uuid', 'exists:splits,id'],
            'splits.*.account_id' => ['required_with:splits', 'uuid', 'exists:accounts,id'],
            'splits.*.amount_num' => ['required_with:splits', 'integer'],
            'splits.*.amount_denom' => ['required_with:splits', 'integer', 'min:1'],
            'splits.*.memo' => ['nullable', 'string', 'max:255'],
            'splits.*.reconciled_state' => ['nullable', 'string', 'in:n,c,y,f,v'],
        ]);

        try {
            $transaction = $this->transactionService->update($transaction, $validated);
            $transaction->load(['splits.account', 'currency']);

            return response()->json([
                'message' => 'Transaction updated successfully',
                'transaction' => $transaction,
            ]);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'splits' => [$e->getMessage()],
            ]);
        }
    }

    /**
     * Delete a transaction.
     */
    public function destroy(Transaction $transaction): JsonResponse
    {
        // Cannot delete posted transactions
        if ($transaction->is_posted) {
            return response()->json([
                'message' => 'Cannot delete a posted transaction. Reverse it first.',
            ], 422);
        }

        $transaction->splits()->delete();
        $transaction->delete();

        return response()->json([
            'message' => 'Transaction deleted successfully',
        ]);
    }

    /**
     * Post a transaction (make it final).
     */
    public function post(Transaction $transaction): JsonResponse
    {
        try {
            $transaction = $this->transactionService->post($transaction);

            return response()->json([
                'message' => 'Transaction posted successfully',
                'transaction' => $transaction,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reverse a posted transaction.
     */
    public function reverse(Request $request, Transaction $transaction): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $reversalTransaction = $this->transactionService->reverse(
                $transaction,
                $validated['reason'] ?? 'Transaction reversal'
            );

            $reversalTransaction->load(['splits.account', 'currency']);

            return response()->json([
                'message' => 'Transaction reversed successfully',
                'original_transaction' => $transaction->fresh(),
                'reversal_transaction' => $reversalTransaction,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Void a transaction (mark as cancelled without creating reversal).
     */
    public function void(Request $request, Transaction $transaction): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $transaction = $this->transactionService->void($transaction, $validated['reason']);

            return response()->json([
                'message' => 'Transaction voided successfully',
                'transaction' => $transaction,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update reconciliation state of splits.
     */
    public function reconcile(Request $request, Transaction $transaction): JsonResponse
    {
        $validated = $request->validate([
            'splits' => ['required', 'array', 'min:1'],
            'splits.*.id' => ['required', 'uuid', 'exists:splits,id'],
            'splits.*.reconciled_state' => ['required', 'string', 'in:n,c,y,f,v'],
            'splits.*.reconciled_date' => ['nullable', 'date'],
        ]);

        foreach ($validated['splits'] as $splitData) {
            $split = $transaction->splits()->find($splitData['id']);
            if ($split) {
                $split->update([
                    'reconciled_state' => $splitData['reconciled_state'],
                    'reconciled_date' => $splitData['reconciled_date'] ?? ($splitData['reconciled_state'] !== 'n' ? now() : null),
                ]);
            }
        }

        $transaction->load(['splits.account', 'currency']);

        return response()->json([
            'message' => 'Reconciliation updated successfully',
            'transaction' => $transaction,
        ]);
    }

    /**
     * Validate transaction splits without saving.
     */
    public function validate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'splits' => ['required', 'array', 'min:2'],
            'splits.*.account_id' => ['required', 'uuid', 'exists:accounts,id'],
            'splits.*.amount_num' => ['required', 'integer'],
            'splits.*.amount_denom' => ['required', 'integer', 'min:1'],
        ]);

        $isValid = $this->transactionService->validate($validated['splits']);

        // Calculate totals
        $debits = 0;
        $credits = 0;
        foreach ($validated['splits'] as $split) {
            $amount = $split['amount_num'] / $split['amount_denom'];
            if ($amount > 0) {
                $debits += $amount;
            } else {
                $credits += abs($amount);
            }
        }

        return response()->json([
            'is_valid' => $isValid,
            'debits' => $debits,
            'credits' => $credits,
            'difference' => abs($debits - $credits),
        ]);
    }
}
