<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduledTransaction;
use App\Models\Transaction;
use App\Services\RecurrenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScheduledTransactionController extends Controller
{
    public function __construct(
        protected RecurrenceService $recurrenceService
    ) {}

    /**
     * List all scheduled transactions for the current company.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ScheduledTransaction::query()
            ->with(['currency']);

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by frequency
        if ($request->has('frequency')) {
            $query->where('frequency', $request->frequency);
        }

        // Filter due soon
        if ($request->has('due_within_days')) {
            $query->where('next_occurrence', '<=', now()->addDays((int) $request->due_within_days));
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $scheduled = $query
            ->orderBy('next_occurrence')
            ->paginate($request->input('per_page', 20));

        return response()->json($scheduled);
    }

    /**
     * Get a single scheduled transaction.
     */
    public function show(ScheduledTransaction $scheduledTransaction): JsonResponse
    {
        $scheduledTransaction->load(['currency']);

        return response()->json(['data' => $scheduledTransaction]);
    }

    /**
     * Create a new scheduled transaction.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'frequency' => ['required', Rule::in(['daily', 'weekly', 'bi-weekly', 'monthly', 'quarterly', 'yearly'])],
            'frequency_interval' => 'integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'month_of_year' => 'nullable|integer|min:1|max:12',
            'auto_create' => 'boolean',
            'auto_post' => 'boolean',
            'max_occurrences' => 'nullable|integer|min:1',
            'splits' => 'required|array|min:2',
            'splits.*.account_id' => 'required|uuid|exists:accounts,id',
            'splits.*.amount' => 'required|numeric|min:0',
            'splits.*.action' => ['required', Rule::in(['DEBIT', 'CREDIT'])],
        ]);

        $scheduled = $this->recurrenceService->create($validated);

        return response()->json([
            'message' => 'Scheduled transaction created successfully',
            'data' => $scheduled,
        ], 201);
    }

    /**
     * Update a scheduled transaction.
     */
    public function update(Request $request, ScheduledTransaction $scheduledTransaction): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'frequency' => [Rule::in(['daily', 'weekly', 'bi-weekly', 'monthly', 'quarterly', 'yearly'])],
            'frequency_interval' => 'integer|min:1',
            'end_date' => 'nullable|date',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'month_of_year' => 'nullable|integer|min:1|max:12',
            'auto_create' => 'boolean',
            'auto_post' => 'boolean',
            'max_occurrences' => 'nullable|integer|min:1',
        ]);

        $scheduled = $this->recurrenceService->update($scheduledTransaction, $validated);

        return response()->json([
            'message' => 'Scheduled transaction updated successfully',
            'data' => $scheduled,
        ]);
    }

    /**
     * Delete a scheduled transaction.
     */
    public function destroy(ScheduledTransaction $scheduledTransaction): JsonResponse
    {
        $scheduledTransaction->delete();

        return response()->json([
            'message' => 'Scheduled transaction deleted successfully',
        ]);
    }

    /**
     * Create a transaction from a scheduled template now.
     */
    public function createNow(ScheduledTransaction $scheduledTransaction): JsonResponse
    {
        try {
            $transaction = $this->recurrenceService->createFromScheduled($scheduledTransaction);

            return response()->json([
                'message' => 'Transaction created successfully',
                'data' => [
                    'transaction' => $transaction,
                    'scheduled' => $scheduledTransaction->fresh(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Skip the next occurrence.
     */
    public function skipNext(ScheduledTransaction $scheduledTransaction): JsonResponse
    {
        $scheduled = $this->recurrenceService->skipNext($scheduledTransaction);

        return response()->json([
            'message' => 'Next occurrence skipped',
            'data' => $scheduled,
        ]);
    }

    /**
     * Pause a scheduled transaction.
     */
    public function pause(ScheduledTransaction $scheduledTransaction): JsonResponse
    {
        $scheduled = $this->recurrenceService->pause($scheduledTransaction);

        return response()->json([
            'message' => 'Scheduled transaction paused',
            'data' => $scheduled,
        ]);
    }

    /**
     * Resume a paused scheduled transaction.
     */
    public function resume(ScheduledTransaction $scheduledTransaction): JsonResponse
    {
        $scheduled = $this->recurrenceService->resume($scheduledTransaction);

        return response()->json([
            'message' => 'Scheduled transaction resumed',
            'data' => $scheduled,
        ]);
    }

    /**
     * Get due scheduled transactions.
     */
    public function due(Request $request): JsonResponse
    {
        $company = auth()->user()->currentCompany ?? auth()->user()->companies()->first();
        
        $due = $this->recurrenceService->getDueTransactions($company);

        return response()->json(['data' => $due]);
    }

    /**
     * End a scheduled transaction series.
     */
    public function endSeries(Request $request, ScheduledTransaction $scheduledTransaction): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $scheduled = $this->recurrenceService->endSeries(
            $scheduledTransaction,
            $validated['reason'] ?? null
        );

        return response()->json([
            'message' => 'Scheduled transaction series ended',
            'data' => $scheduled,
        ]);
    }

    /**
     * Get upcoming scheduled transactions.
     */
    public function upcoming(Request $request): JsonResponse
    {
        $company = auth()->user()->currentCompany ?? auth()->user()->companies()->first();
        $days = $request->input('days', 30);

        $upcoming = $this->recurrenceService->getUpcoming($company, $days);

        return response()->json(['data' => $upcoming]);
    }

    /**
     * Process all due scheduled transactions.
     */
    public function processAllDue(): JsonResponse
    {
        $results = $this->recurrenceService->processAllDue();

        return response()->json([
            'message' => 'Scheduled transactions processed',
            'data' => $results,
        ]);
    }

    /**
     * Get schedule summary.
     */
    public function summary(): JsonResponse
    {
        $company = auth()->user()->currentCompany ?? auth()->user()->companies()->first();
        
        $summary = $this->recurrenceService->getSummary($company);

        return response()->json(['data' => $summary]);
    }
}
