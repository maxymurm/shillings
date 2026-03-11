<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Services\BudgetService;
use App\ValueObjects\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BudgetController extends Controller
{
    public function __construct(
        protected BudgetService $budgetService
    ) {}

    /**
     * List all budgets for the current company.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Budget::query()
            ->with(['accounts.account']);

        // Filter by fiscal year
        if ($request->has('fiscal_year')) {
            $query->where('fiscal_year', $request->fiscal_year);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $budgets = $query
            ->orderBy('fiscal_year', 'desc')
            ->orderBy('name')
            ->paginate($request->input('per_page', 20));

        return response()->json($budgets);
    }

    /**
     * Get a single budget.
     */
    public function show(Budget $budget): JsonResponse
    {
        $budget->load(['accounts.account']);

        return response()->json(['data' => $budget]);
    }

    /**
     * Create a new budget.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'fiscal_year' => 'required|integer|min:2000|max:2100',
            'num_periods' => 'required|integer|min:1|max:52',
            'recurrence' => ['required', Rule::in(['monthly', 'quarterly', 'annual'])],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
            'accounts' => 'nullable|array',
            'accounts.*.account_id' => 'required_with:accounts|uuid|exists:accounts,id',
            'accounts.*.period_num' => 'required_with:accounts|integer|min:1',
            'accounts.*.amount' => 'required_with:accounts|numeric',
        ]);

        // Transform accounts to include GnuCash precision
        $accounts = null;
        if (isset($validated['accounts'])) {
            $accounts = collect($validated['accounts'])->map(function ($account) {
                $amount = Money::fromDecimal($account['amount'], 'KES');
                return [
                    'account_id' => $account['account_id'],
                    'period_num' => $account['period_num'],
                    'amount_num' => $amount->getNumerator(),
                    'amount_denom' => $amount->getDenominator(),
                ];
            })->toArray();
        }

        $budget = $this->budgetService->create($validated, $accounts);

        return response()->json([
            'message' => 'Budget created successfully',
            'data' => $budget,
        ], 201);
    }

    /**
     * Update a budget.
     */
    public function update(Request $request, Budget $budget): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'num_periods' => 'integer|min:1|max:52',
            'start_date' => 'date',
            'end_date' => 'date|after:start_date',
            'is_active' => 'boolean',
            'accounts' => 'nullable|array',
            'accounts.*.id' => 'nullable|uuid',
            'accounts.*.account_id' => 'required_with:accounts|uuid|exists:accounts,id',
            'accounts.*.period_num' => 'required_with:accounts|integer|min:1',
            'accounts.*.amount' => 'required_with:accounts|numeric',
        ]);

        $accounts = null;
        if (isset($validated['accounts'])) {
            $accounts = collect($validated['accounts'])->map(function ($account) {
                $amount = Money::fromDecimal($account['amount'], 'KES');
                return [
                    'id' => $account['id'] ?? null,
                    'account_id' => $account['account_id'],
                    'period_num' => $account['period_num'],
                    'amount_num' => $amount->getNumerator(),
                    'amount_denom' => $amount->getDenominator(),
                ];
            })->toArray();
        }

        $budget = $this->budgetService->update($budget, $validated, $accounts);

        return response()->json([
            'message' => 'Budget updated successfully',
            'data' => $budget,
        ]);
    }

    /**
     * Delete a budget.
     */
    public function destroy(Budget $budget): JsonResponse
    {
        $budget->budgetAccounts()->delete();
        $budget->forceDelete(); // Use forceDelete for actual removal

        return response()->json([
            'message' => 'Budget deleted successfully',
        ]);
    }

    /**
     * Clone a budget for a new fiscal year.
     */
    public function clone(Request $request, Budget $budget): JsonResponse
    {
        $validated = $request->validate([
            'new_year' => 'required|integer|min:2000|max:2100',
        ]);

        $newBudget = $this->budgetService->cloneForNewYear($budget, $validated['new_year']);

        return response()->json([
            'message' => 'Budget cloned successfully',
            'data' => $newBudget,
        ], 201);
    }

    /**
     * Get budget vs actual report.
     */
    public function vsActual(Request $request, Budget $budget): JsonResponse
    {
        $report = $this->budgetService->getBudgetVsActual(
            $budget,
            $request->period ? (int) $request->period : null
        );

        return response()->json(['data' => $report]);
    }

    /**
     * Get budget utilization summary.
     */
    public function utilization(Budget $budget): JsonResponse
    {
        $summary = $this->budgetService->getUtilizationSummary($budget);

        return response()->json(['data' => $summary]);
    }

    /**
     * Get budget forecast.
     */
    public function forecast(Budget $budget): JsonResponse
    {
        $forecast = $this->budgetService->getForecast($budget);

        return response()->json(['data' => $forecast]);
    }

    /**
     * Distribute an annual amount across periods.
     */
    public function distribute(Request $request, Budget $budget): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|uuid|exists:accounts,id',
            'annual_amount' => 'required|numeric|min:0',
            'method' => ['required', Rule::in(['equal', 'front_loaded', 'back_loaded'])],
        ]);

        $amount = Money::fromDecimal($validated['annual_amount'], 'KES');
        
        $this->budgetService->distributeAmount(
            $budget,
            $validated['account_id'],
            $amount,
            $validated['method']
        );

        return response()->json([
            'message' => 'Amount distributed successfully',
            'data' => $budget->fresh(['accounts']),
        ]);
    }

    /**
     * Get active budgets.
     */
    public function active(): JsonResponse
    {
        $company = auth()->user()->currentCompany ?? auth()->user()->companies()->first();
        
        $budgets = $this->budgetService->getActiveBudgets($company);

        return response()->json(['data' => $budgets]);
    }
}
