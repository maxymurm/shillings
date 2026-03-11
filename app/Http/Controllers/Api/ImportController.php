<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Models\Account;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ImportController extends Controller
{
    public function __construct(
        protected ImportService $importService
    ) {}

    /**
     * List all import batches for the current company.
     */
    public function index(Request $request): JsonResponse
    {
        $company = auth()->user()->currentCompany ?? auth()->user()->companies()->first();
        
        $batches = $this->importService->getBatches(
            $company,
            $request->status
        );

        return response()->json(['data' => $batches]);
    }

    /**
     * Get a single import batch.
     */
    public function show(ImportBatch $importBatch): JsonResponse
    {
        $importBatch->load('account');

        return response()->json(['data' => $importBatch]);
    }

    /**
     * Preview a CSV file.
     */
    public function previewCSV(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
            'rows' => 'integer|min:1|max:20',
        ]);

        $content = file_get_contents($validated['file']->getRealPath());
        $preview = $this->importService->getCSVPreview(
            $content,
            $validated['rows'] ?? 5
        );

        return response()->json(['data' => $preview]);
    }

    /**
     * Upload and parse a file.
     */
    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|max:10240',
            'account_id' => 'required|uuid|exists:accounts,id',
            'file_type' => ['required', Rule::in(['csv', 'ofx'])],
            'mapping' => 'required_if:file_type,csv|array',
            'mapping.date' => 'required_if:file_type,csv|string',
            'mapping.amount' => 'required_if:file_type,csv|string',
            'mapping.description' => 'nullable|string',
            'mapping.reference' => 'nullable|string',
        ]);

        $company = auth()->user()->currentCompany ?? auth()->user()->companies()->first();
        $account = Account::findOrFail($validated['account_id']);
        $content = file_get_contents($validated['file']->getRealPath());

        // Parse based on file type
        if ($validated['file_type'] === 'csv') {
            $transactions = $this->importService->parseCSV($content, $validated['mapping']);
        } else {
            $transactions = $this->importService->parseOFX($content);
        }

        // Create batch
        $batch = $this->importService->createBatch(
            $company,
            $account,
            $validated['file']->getClientOriginalName(),
            $validated['file_type'],
            $transactions
        );

        // Store transactions in session or cache for processing
        cache()->put("import_batch_{$batch->id}", $transactions, now()->addHours(1));

        return response()->json([
            'message' => 'File uploaded and parsed successfully',
            'data' => [
                'batch' => $batch,
                'transaction_count' => count($transactions),
                'preview' => array_slice($transactions, 0, 5),
            ],
        ], 201);
    }

    /**
     * Process an import batch.
     */
    public function process(Request $request, ImportBatch $importBatch): JsonResponse
    {
        if ($importBatch->status !== 'pending') {
            return response()->json([
                'message' => 'Batch has already been processed',
            ], 422);
        }

        $validated = $request->validate([
            'offset_account_id' => 'required|uuid|exists:accounts,id',
        ]);

        $transactions = cache()->get("import_batch_{$importBatch->id}");
        
        if (!$transactions) {
            return response()->json([
                'message' => 'Import data has expired. Please upload the file again.',
            ], 422);
        }

        $offsetAccount = Account::findOrFail($validated['offset_account_id']);
        
        $batch = $this->importService->processBatch($importBatch, $transactions, $offsetAccount);

        // Clear cache
        cache()->forget("import_batch_{$importBatch->id}");

        return response()->json([
            'message' => 'Import processed successfully',
            'data' => $batch,
        ]);
    }

    /**
     * Match transactions.
     */
    public function match(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|uuid|exists:accounts,id',
            'transactions' => 'required|array',
            'transactions.*.date' => 'required|date',
            'transactions.*.amount_num' => 'required|integer',
            'transactions.*.amount_denom' => 'required|integer|min:1',
            'transactions.*.is_debit' => 'boolean',
        ]);

        $account = Account::findOrFail($validated['account_id']);
        $matches = $this->importService->matchTransactions($account, $validated['transactions']);

        return response()->json(['data' => $matches]);
    }

    /**
     * Delete an import batch.
     */
    public function destroy(ImportBatch $importBatch): JsonResponse
    {
        if ($importBatch->status === 'processing') {
            return response()->json([
                'message' => 'Cannot delete batch that is currently processing',
            ], 422);
        }

        cache()->forget("import_batch_{$importBatch->id}");
        $importBatch->delete();

        return response()->json([
            'message' => 'Import batch deleted successfully',
        ]);
    }
}
