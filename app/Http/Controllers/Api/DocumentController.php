<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Account;
use App\Services\DocumentService;
use App\ValueObjects\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $documentService
    ) {}

    /**
     * List all documents for the current company.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Document::query()
            ->with(['contact', 'items']);

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by contact
        if ($request->has('contact_id')) {
            $query->where('contact_id', $request->contact_id);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('issued_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('issued_at', '<=', $request->end_date);
        }

        // Filter overdue
        if ($request->boolean('overdue')) {
            $query->whereIn('status', ['sent', 'partial'])
                ->where('due_at', '<', now());
        }

        // Search by document number
        if ($request->has('search')) {
            $query->where('document_number', 'like', "%{$request->search}%");
        }

        $documents = $query
            ->orderBy('issued_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($documents);
    }

    /**
     * Get a single document.
     */
    public function show(Document $document): JsonResponse
    {
        $document->load(['contact', 'items.account', 'items.tax']);

        return response()->json(['data' => $document]);
    }

    /**
     * Create a new document.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['invoice', 'bill', 'quote', 'credit_note', 'debit_note', 'receipt'])],
            'contact_id' => 'required|uuid|exists:contacts,id',
            'document_number' => 'nullable|string|max:50',
            'issued_at' => 'required|date',
            'due_at' => 'nullable|date|after_or_equal:issued_at',
            'currency_code' => 'required|string|size:3|exists:currencies,code',
            'bill_term_id' => 'nullable|uuid|exists:bill_terms,id',
            'order_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'footer' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.account_id' => 'required|uuid|exists:accounts,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_id' => 'nullable|uuid|exists:taxes,id',
        ]);

        // Transform items to include GnuCash precision
        $items = collect($validated['items'])->map(function ($item) {
            $price = Money::fromDecimal($item['price'], 'KES');
            return [
                'account_id' => $item['account_id'],
                'name' => $item['description'], // Map description to name
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'price_num' => $price->getNumerator(),
                'price_denom' => $price->getDenominator(),
                'discount_percent' => $item['discount_percent'] ?? 0,
                'tax_id' => $item['tax_id'] ?? null,
            ];
        })->toArray();

        $document = $this->documentService->create($validated, $items);

        return response()->json([
            'message' => 'Document created successfully',
            'data' => $document,
        ], 201);
    }

    /**
     * Update a document.
     */
    public function update(Request $request, Document $document): JsonResponse
    {
        if (!$document->isEditable()) {
            return response()->json([
                'message' => 'Document cannot be edited',
            ], 422);
        }

        $validated = $request->validate([
            'contact_id' => 'uuid|exists:contacts,id',
            'issued_at' => 'date',
            'due_at' => 'nullable|date|after_or_equal:issued_at',
            'order_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'footer' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.id' => 'nullable|uuid',
            'items.*.account_id' => 'required_with:items|uuid|exists:accounts,id',
            'items.*.description' => 'required_with:items|string|max:255',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.price' => 'required_with:items|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_id' => 'nullable|uuid|exists:taxes,id',
        ]);

        $items = null;
        if (isset($validated['items'])) {
            $items = collect($validated['items'])->map(function ($item) {
                $price = Money::fromDecimal($item['price'], 'KES');
                return [
                    'id' => $item['id'] ?? null,
                    'account_id' => $item['account_id'],
                    'name' => $item['description'], // Map description to name
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'price_num' => $price->getNumerator(),
                    'price_denom' => $price->getDenominator(),
                    'discount_percent' => $item['discount_percent'] ?? 0,
                    'tax_id' => $item['tax_id'] ?? null,
                ];
            })->toArray();
        }

        $document = $this->documentService->update($document, $validated, $items);

        return response()->json([
            'message' => 'Document updated successfully',
            'data' => $document,
        ]);
    }

    /**
     * Delete a document.
     */
    public function destroy(Document $document): JsonResponse
    {
        if (!$document->isEditable()) {
            return response()->json([
                'message' => 'Document cannot be deleted',
            ], 422);
        }

        $document->delete();

        return response()->json([
            'message' => 'Document deleted successfully',
        ]);
    }

    /**
     * Send a document.
     */
    public function send(Document $document): JsonResponse
    {
        try {
            $document = $this->documentService->send($document);
            
            return response()->json([
                'message' => 'Document sent successfully',
                'data' => $document,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Record a payment for a document.
     */
    public function recordPayment(Request $request, Document $document): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_account_id' => 'required|uuid|exists:accounts,id',
            'payment_date' => 'nullable|date',
            'reference' => 'nullable|string|max:100',
        ]);

        try {
            $amount = Money::fromDecimal($validated['amount'], $document->currency_code);
            $paymentAccount = Account::findOrFail($validated['payment_account_id']);

            $transaction = $this->documentService->recordPayment(
                $document,
                $amount,
                $paymentAccount,
                $validated['payment_date'] ? \Carbon\Carbon::parse($validated['payment_date']) : null,
                $validated['reference'] ?? null
            );

            return response()->json([
                'message' => 'Payment recorded successfully',
                'data' => [
                    'document' => $document->fresh(),
                    'transaction' => $transaction,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel a document.
     */
    public function cancel(Request $request, Document $document): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $document = $this->documentService->cancel($document, $validated['reason']);

            return response()->json([
                'message' => 'Document cancelled successfully',
                'data' => $document,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Convert a quote to an invoice.
     */
    public function convertToInvoice(Document $document): JsonResponse
    {
        try {
            $invoice = $this->documentService->convertQuoteToInvoice($document);

            return response()->json([
                'message' => 'Quote converted to invoice successfully',
                'data' => $invoice,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Duplicate a document.
     */
    public function duplicate(Document $document): JsonResponse
    {
        $newDocument = $this->documentService->duplicate($document);

        return response()->json([
            'message' => 'Document duplicated successfully',
            'data' => $newDocument,
        ], 201);
    }

    /**
     * Create a credit note for an invoice.
     */
    public function createCreditNote(Request $request, Document $document): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.account_id' => 'required|uuid|exists:accounts,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_id' => 'nullable|uuid|exists:taxes,id',
        ]);

        try {
            $items = collect($validated['items'])->map(function ($item) {
                $price = Money::fromDecimal($item['price'], 'KES');
                return [
                    'account_id' => $item['account_id'],
                    'name' => $item['description'], // Map description to name
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'price_num' => $price->getNumerator(),
                    'price_denom' => $price->getDenominator(),
                    'tax_id' => $item['tax_id'] ?? null,
                ];
            })->toArray();

            $creditNote = $this->documentService->createCreditNote(
                $document,
                $items,
                $validated['reason'] ?? null
            );

            return response()->json([
                'message' => 'Credit note created successfully',
                'data' => $creditNote,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get overdue documents.
     */
    public function overdue(Request $request): JsonResponse
    {
        $company = auth()->user()->currentCompany ?? auth()->user()->companies()->first();
        
        $documents = $this->documentService->getOverdueDocuments(
            $company,
            $request->type
        );

        return response()->json(['data' => $documents]);
    }

    /**
     * Get document summary for dashboard.
     */
    public function summary(): JsonResponse
    {
        $company = auth()->user()->currentCompany ?? auth()->user()->companies()->first();
        
        $summary = $this->documentService->getSummary($company);

        return response()->json(['data' => $summary]);
    }
}
