<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use App\Services\TaxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaxController extends Controller
{
    public function __construct(
        protected TaxService $taxService
    ) {}

    /**
     * List all taxes for the current company.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tax::query()
            ->with(['rules', 'account']);

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by enabled status
        if ($request->has('enabled')) {
            $query->where('enabled', filter_var($request->enabled, FILTER_VALIDATE_BOOLEAN));
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $taxes = $query
            ->orderBy('name')
            ->paginate($request->input('per_page', 50));

        return response()->json($taxes);
    }

    /**
     * Get a single tax.
     */
    public function show(Tax $tax): JsonResponse
    {
        $tax->load(['rules', 'account']);

        return response()->json(['data' => $tax]);
    }

    /**
     * Create a new tax.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
            'type' => ['required', Rule::in(['percentage', 'fixed'])],
            'is_compound' => 'boolean',
            'is_recoverable' => 'boolean',
            'account_id' => 'nullable|uuid|exists:accounts,id',
            'enabled' => 'boolean',
            'rules' => 'nullable|array',
            'rules.*.applies_to' => ['required_with:rules', Rule::in(['sales', 'purchases', 'both'])],
            'rules.*.account_id' => 'nullable|uuid|exists:accounts,id',
            'rules.*.contact_type' => ['nullable', Rule::in(['customer', 'vendor'])],
            'rules.*.region' => 'nullable|string|max:100',
            'rules.*.priority' => 'integer|min:0',
        ]);

        // Convert rate to GnuCash precision
        $validated['rate_num'] = (int) ($validated['rate'] * 100);
        $validated['rate_denom'] = 10000; // For 2 decimal places precision
        unset($validated['rate']);

        $tax = $this->taxService->create($validated, $validated['rules'] ?? null);

        return response()->json([
            'message' => 'Tax created successfully',
            'data' => $tax,
        ], 201);
    }

    /**
     * Update a tax.
     */
    public function update(Request $request, Tax $tax): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'rate' => 'numeric|min:0|max:100',
            'type' => [Rule::in(['percentage', 'fixed'])],
            'is_compound' => 'boolean',
            'is_recoverable' => 'boolean',
            'account_id' => 'nullable|uuid|exists:accounts,id',
            'enabled' => 'boolean',
            'rules' => 'nullable|array',
        ]);

        if (isset($validated['rate'])) {
            $validated['rate_num'] = (int) ($validated['rate'] * 100);
            $validated['rate_denom'] = 10000;
            unset($validated['rate']);
        }

        $tax = $this->taxService->update($tax, $validated, $validated['rules'] ?? null);

        return response()->json([
            'message' => 'Tax updated successfully',
            'data' => $tax,
        ]);
    }

    /**
     * Delete a tax.
     */
    public function destroy(Tax $tax): JsonResponse
    {
        // Check if tax is in use
        if ($tax->documentItems()->exists()) {
            return response()->json([
                'message' => 'Cannot delete tax that is in use',
            ], 422);
        }

        $tax->rules()->delete();
        $tax->delete();

        return response()->json([
            'message' => 'Tax deleted successfully',
        ]);
    }

    /**
     * Get applicable taxes for a document type.
     */
    public function applicable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_type' => ['required', Rule::in(['invoice', 'bill', 'quote', 'credit_note', 'debit_note'])],
            'account_id' => 'nullable|uuid|exists:accounts,id',
            'contact_id' => 'nullable|uuid|exists:contacts,id',
        ]);

        $company = auth()->user()->currentCompany ?? auth()->user()->companies()->first();
        
        $account = isset($validated['account_id']) 
            ? \App\Models\Account::find($validated['account_id']) 
            : null;
        
        $contact = isset($validated['contact_id'])
            ? \App\Models\Contact::find($validated['contact_id'])
            : null;

        $taxes = $this->taxService->getApplicableTaxes(
            $company,
            $validated['document_type'],
            $account,
            $contact
        );

        return response()->json(['data' => $taxes]);
    }

    /**
     * Get tax summary report.
     */
    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $company = auth()->user()->currentCompany ?? auth()->user()->companies()->first();
        
        $summary = $this->taxService->getTaxSummary(
            $company,
            $validated['start_date'],
            $validated['end_date']
        );

        return response()->json(['data' => $summary]);
    }

    /**
     * Get recoverable tax for a period.
     */
    public function recoverable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $company = auth()->user()->currentCompany ?? auth()->user()->companies()->first();
        
        $recoverable = $this->taxService->getRecoverableTax(
            $company,
            $validated['start_date'],
            $validated['end_date']
        );

        return response()->json(['data' => ['amount' => $recoverable]]);
    }

    /**
     * Validate a tax number.
     */
    public function validateTaxNumber(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tax_number' => 'required|string',
            'country' => 'nullable|string|size:2',
        ]);

        $isValid = $this->taxService->validateTaxNumber(
            $validated['tax_number'],
            $validated['country'] ?? 'KE'
        );

        return response()->json([
            'data' => [
                'is_valid' => $isValid,
                'tax_number' => $validated['tax_number'],
                'country' => $validated['country'] ?? 'KE',
            ],
        ]);
    }
}
