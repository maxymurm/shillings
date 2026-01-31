<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function __construct(
        protected AccountService $accountService
    ) {}

    /**
     * List all accounts for the current company.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Account::query()
            ->with(['accountType', 'currency', 'parent']);

        // Filter by account type
        if ($request->has('type')) {
            $query->whereHas('accountType', fn ($q) => $q->where('type', $request->type));
        }

        // Filter by parent
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        // Search by name or code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Get as tree or flat list
        if ($request->boolean('tree')) {
            $accounts = $query->whereNull('parent_id')
                ->with('descendants')
                ->orderBy('code')
                ->get();
        } else {
            $accounts = $query
                ->orderBy('path')
                ->paginate($request->input('per_page', 50));
        }

        return response()->json($accounts);
    }

    /**
     * Get a single account.
     */
    public function show(Account $account): JsonResponse
    {
        $account->load(['accountType', 'currency', 'parent', 'children']);

        return response()->json([
            'account' => $account,
            'balance' => $this->accountService->getBalance($account),
        ]);
    }

    /**
     * Create a new account.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'account_type_id' => ['required', 'uuid', 'exists:account_types,id'],
            'currency_id' => ['required', 'uuid', 'exists:currencies,id'],
            'parent_id' => ['nullable', 'uuid', 'exists:accounts,id'],
            'is_placeholder' => ['boolean'],
            'is_active' => ['boolean'],
            'settings' => ['nullable', 'array'],
        ]);

        $account = Account::create($validated);
        $account->load(['accountType', 'currency', 'parent']);

        return response()->json([
            'message' => 'Account created successfully',
            'account' => $account,
        ], 201);
    }

    /**
     * Update an account.
     */
    public function update(Request $request, Account $account): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'account_type_id' => ['sometimes', 'uuid', 'exists:account_types,id'],
            'currency_id' => ['sometimes', 'uuid', 'exists:currencies,id'],
            'parent_id' => [
                'nullable',
                'uuid',
                'exists:accounts,id',
                // Prevent circular reference
                Rule::notIn([$account->id]),
            ],
            'is_placeholder' => ['boolean'],
            'is_active' => ['boolean'],
            'settings' => ['nullable', 'array'],
        ]);

        // Check if new parent would create circular reference
        if (isset($validated['parent_id']) && $validated['parent_id']) {
            $potentialParent = Account::find($validated['parent_id']);
            if ($potentialParent && str_starts_with($potentialParent->path, $account->path)) {
                return response()->json([
                    'message' => 'Cannot set a descendant as parent (circular reference)',
                ], 422);
            }
        }

        $account->update($validated);
        $account->load(['accountType', 'currency', 'parent']);

        return response()->json([
            'message' => 'Account updated successfully',
            'account' => $account,
        ]);
    }

    /**
     * Delete an account.
     */
    public function destroy(Account $account): JsonResponse
    {
        // Check if account has children
        if ($account->children()->exists()) {
            return response()->json([
                'message' => 'Cannot delete account with child accounts',
            ], 422);
        }

        // Check if account has transactions
        if ($account->splits()->exists()) {
            return response()->json([
                'message' => 'Cannot delete account with transactions',
            ], 422);
        }

        $account->delete();

        return response()->json([
            'message' => 'Account deleted successfully',
        ]);
    }

    /**
     * Get account balance.
     */
    public function balance(Request $request, Account $account): JsonResponse
    {
        $includeChildren = $request->boolean('include_children', false);
        $asOf = $request->date('as_of');

        $balance = $this->accountService->getBalance($account, $includeChildren, $asOf);

        // Get balance in different currency if requested
        $convertedBalance = null;
        if ($request->has('currency_id')) {
            $convertedBalance = $this->accountService->getBalanceInCurrency(
                $account,
                $request->currency_id,
                $includeChildren,
                $asOf
            );
        }

        return response()->json([
            'account_id' => $account->id,
            'balance' => $balance,
            'currency' => $account->currency,
            'include_children' => $includeChildren,
            'as_of' => $asOf?->toDateString(),
            'converted_balance' => $convertedBalance,
        ]);
    }

    /**
     * Get running balance (transactions with cumulative balance).
     */
    public function runningBalance(Request $request, Account $account): JsonResponse
    {
        $startDate = $request->date('start_date');
        $endDate = $request->date('end_date');

        $transactions = $this->accountService->getRunningBalance($account, $startDate, $endDate);

        return response()->json([
            'account_id' => $account->id,
            'transactions' => $transactions,
            'start_date' => $startDate?->toDateString(),
            'end_date' => $endDate?->toDateString(),
        ]);
    }

    /**
     * Get trial balance for all accounts.
     */
    public function trialBalance(Request $request): JsonResponse
    {
        $asOf = $request->date('as_of');

        $trialBalance = $this->accountService->getTrialBalance($asOf);

        return response()->json([
            'trial_balance' => $trialBalance,
            'as_of' => $asOf?->toDateString() ?? now()->toDateString(),
        ]);
    }

    /**
     * Get chart of accounts (hierarchical).
     */
    public function chartOfAccounts(): JsonResponse
    {
        $accounts = Account::with(['accountType', 'currency'])
            ->whereNull('parent_id')
            ->with('descendants')
            ->orderBy('code')
            ->get()
            ->map(function ($account) {
                return $this->formatAccountTree($account);
            });

        return response()->json([
            'chart_of_accounts' => $accounts,
        ]);
    }

    /**
     * Format account with children for tree view.
     */
    protected function formatAccountTree(Account $account): array
    {
        $balance = $this->accountService->getBalance($account, true);

        return [
            'id' => $account->id,
            'name' => $account->name,
            'code' => $account->code,
            'full_name' => $account->full_name,
            'type' => $account->accountType->type,
            'type_name' => $account->accountType->name,
            'currency' => $account->currency->code,
            'balance' => $balance,
            'formatted_balance' => $account->currency->formatAmount($balance),
            'is_placeholder' => $account->is_placeholder,
            'is_active' => $account->is_active,
            'children' => $account->children->map(fn ($child) => $this->formatAccountTree($child)),
        ];
    }
}
