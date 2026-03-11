<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    /**
     * Get sync status and server timestamp.
     * Client uses this to determine what to download.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'server_time' => now()->toIso8601String(),
                'user_id' => $user->id,
                'sync_supported' => true,
                'api_version' => '1.0',
            ],
        ]);
    }

    /**
     * Batch sync: accept multiple mutations in one request.
     * Returns results for each mutation.
     */
    public function batch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'operations' => 'required|array|max:50',
            'operations.*.type' => 'required|in:CREATE,UPDATE,DELETE',
            'operations.*.entity_type' => 'required|in:transaction',
            'operations.*.entity_id' => 'nullable|string',
            'operations.*.payload' => 'nullable|array',
            'operations.*.client_timestamp' => 'required|integer',
        ]);

        $results = [];

        foreach ($validated['operations'] as $index => $operation) {
            try {
                $result = $this->processOperation($operation, $request);
                $results[] = [
                    'index' => $index,
                    'status' => 'success',
                    'data' => $result,
                ];
            } catch (\Illuminate\Validation\ValidationException $e) {
                $results[] = [
                    'index' => $index,
                    'status' => 'validation_error',
                    'errors' => $e->errors(),
                ];
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                $results[] = [
                    'index' => $index,
                    'status' => 'not_found',
                    'message' => 'Entity not found',
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'index' => $index,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'data' => [
                'results' => $results,
                'server_time' => now()->toIso8601String(),
                'processed' => count(array_filter($results, fn ($r) => $r['status'] === 'success')),
                'failed' => count(array_filter($results, fn ($r) => $r['status'] !== 'success')),
            ],
        ]);
    }

    /**
     * Process a single sync operation.
     */
    protected function processOperation(array $operation, Request $request): array
    {
        $type = $operation['type'];
        $entityType = $operation['entity_type'];
        $payload = $operation['payload'] ?? [];

        switch ($entityType) {
            case 'transaction':
                return $this->processTransactionOperation($type, $operation, $payload, $request);
            default:
                throw new \Exception("Unsupported entity type: {$entityType}");
        }
    }

    /**
     * Process a transaction sync operation using TransactionService directly.
     */
    protected function processTransactionOperation(string $type, array $operation, array $payload, Request $request): array
    {
        switch ($type) {
            case 'CREATE':
                $transactionData = [
                    'company_id' => $payload['company_id'],
                    'description' => $payload['description'] ?? null,
                    'transaction_date' => $payload['transaction_date'] ?? $payload['post_date'] ?? now()->toDateString(),
                    'num' => $payload['num'] ?? null,
                    'notes' => $payload['notes'] ?? null,
                    'created_by' => $request->user()->id,
                ];

                $splitsData = collect($payload['splits'] ?? [])->map(function ($split) {
                    // TransactionService expects decimal 'amount' and 'action'
                    $amount = isset($split['amount'])
                        ? (float) $split['amount']
                        : (isset($split['amount_num'], $split['amount_denom'])
                            ? $split['amount_num'] / $split['amount_denom']
                            : 0);

                    return [
                        'account_id' => $split['account_id'],
                        'amount' => $amount,
                        'action' => $split['action'] ?? 'DEBIT',
                        'memo' => $split['memo'] ?? null,
                    ];
                })->toArray();

                $transaction = $this->transactionService->create($transactionData, $splitsData);

                return [
                    'server_id' => $transaction->id,
                    'client_id' => $operation['entity_id'],
                ];

            case 'UPDATE':
                $entityId = $operation['entity_id'];
                $transaction = Transaction::findOrFail($entityId);

                $transactionData = array_filter([
                    'transaction_date' => $payload['transaction_date'] ?? $payload['post_date'] ?? null,
                    'description' => $payload['description'] ?? null,
                    'num' => $payload['num'] ?? null,
                    'notes' => $payload['notes'] ?? null,
                ], fn ($v) => $v !== null);

                $splitsData = null;
                if (isset($payload['splits'])) {
                    $splitsData = collect($payload['splits'])->map(function ($split) {
                        $amount = isset($split['amount'])
                            ? (float) $split['amount']
                            : (isset($split['amount_num'], $split['amount_denom'])
                                ? $split['amount_num'] / $split['amount_denom']
                                : 0);

                        return [
                            'account_id' => $split['account_id'],
                            'amount' => $amount,
                            'action' => $split['action'] ?? 'DEBIT',
                            'memo' => $split['memo'] ?? null,
                        ];
                    })->toArray();
                }

                $this->transactionService->update($transaction, $transactionData, $splitsData);

                return [
                    'server_id' => $entityId,
                    'updated' => true,
                ];

            case 'DELETE':
                $entityId = $operation['entity_id'];
                $transaction = Transaction::findOrFail($entityId);
                $transaction->splits()->delete();
                $transaction->delete();

                return [
                    'server_id' => $entityId,
                    'deleted' => true,
                ];

            default:
                throw new \Exception("Unknown operation type: {$type}");
        }
    }

    /**
     * Get changes since a given timestamp (for incremental sync).
     */
    public function changes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'since' => 'required|date',
            'entity_types' => 'nullable|array',
            'entity_types.*' => 'in:accounts,transactions,contacts,taxes',
        ]);

        $since = \Carbon\Carbon::parse($validated['since']);
        $entityTypes = $validated['entity_types'] ?? ['accounts', 'transactions', 'contacts', 'taxes'];
        $user = $request->user();

        $changes = [];

        if (in_array('accounts', $entityTypes)) {
            $changes['accounts'] = \App\Models\Account::where('updated_at', '>', $since)
                ->whereHas('company', fn ($q) => $q->whereHas('users', fn ($q2) => $q2->where('users.id', $user->id)))
                ->get();
        }

        if (in_array('transactions', $entityTypes)) {
            $changes['transactions'] = \App\Models\Transaction::with('splits')
                ->where('updated_at', '>', $since)
                ->whereHas('company', fn ($q) => $q->whereHas('users', fn ($q2) => $q2->where('users.id', $user->id)))
                ->limit(100)
                ->get();
        }

        if (in_array('contacts', $entityTypes)) {
            $changes['contacts'] = \App\Models\Contact::where('updated_at', '>', $since)
                ->whereHas('company', fn ($q) => $q->whereHas('users', fn ($q2) => $q2->where('users.id', $user->id)))
                ->get();
        }

        if (in_array('taxes', $entityTypes)) {
            $changes['taxes'] = \App\Models\Tax::where('updated_at', '>', $since)
                ->whereHas('company', fn ($q) => $q->whereHas('users', fn ($q2) => $q2->where('users.id', $user->id)))
                ->get();
        }

        return response()->json([
            'data' => [
                'changes' => $changes,
                'since' => $validated['since'],
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }
}
