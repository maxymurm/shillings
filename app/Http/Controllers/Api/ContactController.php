<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    public function __construct(
        protected ContactService $contactService
    ) {}

    /**
     * List all contacts for the current company.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Contact::query()
            ->with(['persons', 'addresses', 'currency']);

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by enabled status
        if ($request->has('enabled')) {
            $query->where('enabled', filter_var($request->enabled, FILTER_VALIDATE_BOOLEAN));
        }

        // Search by name, email, or tax number
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('tax_number', 'like', "%{$search}%");
            });
        }

        $contacts = $query
            ->orderBy('name')
            ->paginate($request->input('per_page', 50));

        return response()->json($contacts);
    }

    /**
     * Get a single contact.
     */
    public function show(Contact $contact): JsonResponse
    {
        $contact->load(['persons', 'addresses', 'currency', 'documents' => function ($q) {
            $q->latest()->limit(10);
        }]);

        // Add calculated balances
        $contact->receivable_balance = $contact->getReceivableBalance();
        $contact->payable_balance = $contact->getPayableBalance();

        return response()->json(['data' => $contact]);
    }

    /**
     * Create a new contact.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['customer', 'vendor', 'employee', 'other'])],
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'tax_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'currency_code' => 'nullable|string|size:3|exists:currencies,code',
            'enabled' => 'boolean',
            'persons' => 'nullable|array',
            'persons.*.name' => 'required_with:persons|string|max:255',
            'persons.*.email' => 'nullable|email|max:255',
            'persons.*.phone' => 'nullable|string|max:50',
            'persons.*.position' => 'nullable|string|max:100',
            'persons.*.is_primary' => 'boolean',
            'addresses' => 'nullable|array',
            'addresses.*.type' => ['required_with:addresses', Rule::in(['billing', 'shipping', 'office', 'other'])],
            'addresses.*.street' => 'required_with:addresses|string|max:255',
            'addresses.*.city' => 'nullable|string|max:100',
            'addresses.*.state' => 'nullable|string|max:100',
            'addresses.*.zip_code' => 'nullable|string|max:20',
            'addresses.*.country' => 'nullable|string|max:100',
        ]);

        $contact = $this->contactService->create(
            array_merge($validated, ['company_id' => $request->user()->company_id]),
            $validated['persons'] ?? null,
            $validated['addresses'] ?? null
        );

        return response()->json([
            'message' => 'Contact created successfully',
            'data' => $contact,
        ], 201);
    }

    /**
     * Update a contact.
     */
    public function update(Request $request, Contact $contact): JsonResponse
    {
        $validated = $request->validate([
            'type' => [Rule::in(['customer', 'vendor', 'employee', 'other'])],
            'name' => 'string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'tax_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'currency_code' => 'nullable|string|size:3|exists:currencies,code',
            'enabled' => 'boolean',
            'persons' => 'nullable|array',
            'addresses' => 'nullable|array',
        ]);

        $contact = $this->contactService->update(
            $contact,
            $validated,
            $validated['persons'] ?? null,
            $validated['addresses'] ?? null
        );

        return response()->json([
            'message' => 'Contact updated successfully',
            'data' => $contact,
        ]);
    }

    /**
     * Delete a contact.
     */
    public function destroy(Contact $contact): JsonResponse
    {
        // Check for related documents
        if ($contact->documents()->exists()) {
            return response()->json([
                'message' => 'Cannot delete contact with existing documents',
            ], 422);
        }

        $contact->delete();

        return response()->json([
            'message' => 'Contact deleted successfully',
        ]);
    }

    /**
     * Get contact statement.
     */
    public function statement(Request $request, Contact $contact): JsonResponse
    {
        $statement = $this->contactService->getStatement(
            $contact,
            $request->start_date,
            $request->end_date
        );

        return response()->json(['data' => $statement]);
    }

    /**
     * Get contact aging report.
     */
    public function aging(Contact $contact): JsonResponse
    {
        $aging = $this->contactService->getAging($contact);

        return response()->json(['data' => $aging]);
    }

    /**
     * Merge two contacts.
     */
    public function merge(Request $request, Contact $contact): JsonResponse
    {
        $validated = $request->validate([
            'merge_with_id' => 'required|uuid|exists:contacts,id',
        ]);

        $secondary = Contact::findOrFail($validated['merge_with_id']);
        $merged = $this->contactService->merge($contact, $secondary);

        return response()->json([
            'message' => 'Contacts merged successfully',
            'data' => $merged,
        ]);
    }

    /**
     * Get customers with balances.
     */
    public function customersWithBalances(Request $request): JsonResponse
    {
        // TODO: Get current company from auth
        $company = auth()->user()->currentCompany ?? auth()->user()->companies()->first();
        
        $customers = $this->contactService->getCustomersWithBalances($company);

        return response()->json(['data' => $customers]);
    }

    /**
     * Get vendors with balances.
     */
    public function vendorsWithBalances(Request $request): JsonResponse
    {
        $company = auth()->user()->currentCompany ?? auth()->user()->companies()->first();
        
        $vendors = $this->contactService->getVendorsWithBalances($company);

        return response()->json(['data' => $vendors]);
    }
}
