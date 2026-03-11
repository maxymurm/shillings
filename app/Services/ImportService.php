<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\Split;
use App\Models\ImportBatch;
use App\Models\Company;
use App\ValueObjects\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ImportService
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Parse CSV file and return transactions.
     */
    public function parseCSV(string $content, array $mapping): array
    {
        $lines = explode("\n", trim($content));
        $headers = str_getcsv(array_shift($lines));
        $transactions = [];

        foreach ($lines as $lineNum => $line) {
            if (empty(trim($line))) {
                continue;
            }

            $row = str_getcsv($line);
            if (count($row) !== count($headers)) {
                continue;
            }

            $data = array_combine($headers, $row);
            
            $transaction = [
                'line_number' => $lineNum + 2, // Account for header and 0-index
                'raw_data' => $data,
            ];

            // Map fields
            foreach ($mapping as $field => $column) {
                if (isset($data[$column])) {
                    $transaction[$field] = $data[$column];
                }
            }

            // Parse amount
            if (isset($transaction['amount'])) {
                $amount = $this->parseAmount($transaction['amount']);
                $transaction['amount_num'] = $amount['num'];
                $transaction['amount_denom'] = $amount['denom'];
                $transaction['is_debit'] = $amount['is_debit'];
            }

            // Parse date
            if (isset($transaction['date'])) {
                $transaction['date'] = $this->parseDate($transaction['date']);
            }

            $transactions[] = $transaction;
        }

        return $transactions;
    }

    /**
     * Parse OFX file and return transactions.
     */
    public function parseOFX(string $content): array
    {
        $transactions = [];
        
        // Simple OFX parsing (production should use proper OFX library)
        preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/s', $content, $matches);

        foreach ($matches[1] as $index => $trn) {
            $transaction = [
                'line_number' => $index + 1,
                'raw_data' => $trn,
            ];

            // Extract TRNTYPE
            if (preg_match('/<TRNTYPE>([^<]+)/', $trn, $m)) {
                $transaction['type'] = trim($m[1]);
            }

            // Extract DTPOSTED
            if (preg_match('/<DTPOSTED>(\d{8})/', $trn, $m)) {
                $transaction['date'] = Carbon::createFromFormat('Ymd', $m[1])->format('Y-m-d');
            }

            // Extract TRNAMT
            if (preg_match('/<TRNAMT>([^<]+)/', $trn, $m)) {
                $amount = $this->parseAmount(trim($m[1]));
                $transaction['amount_num'] = $amount['num'];
                $transaction['amount_denom'] = $amount['denom'];
                $transaction['is_debit'] = $amount['is_debit'];
            }

            // Extract FITID (transaction ID)
            if (preg_match('/<FITID>([^<]+)/', $trn, $m)) {
                $transaction['reference'] = trim($m[1]);
            }

            // Extract NAME
            if (preg_match('/<NAME>([^<]+)/', $trn, $m)) {
                $transaction['description'] = trim($m[1]);
            }

            // Extract MEMO
            if (preg_match('/<MEMO>([^<]+)/', $trn, $m)) {
                $transaction['memo'] = trim($m[1]);
            }

            $transactions[] = $transaction;
        }

        return $transactions;
    }

    /**
     * Parse amount string into numerator/denominator.
     */
    protected function parseAmount(string $amount): array
    {
        // Remove currency symbols and whitespace
        $amount = preg_replace('/[^0-9.\-,]/', '', $amount);
        
        // Handle European format (1.234,56)
        if (preg_match('/^\d{1,3}(\.\d{3})*(,\d{2})?$/', $amount)) {
            $amount = str_replace('.', '', $amount);
            $amount = str_replace(',', '.', $amount);
        } else {
            // Handle US format (1,234.56)
            $amount = str_replace(',', '', $amount);
        }

        $isDebit = str_starts_with($amount, '-');
        $amount = ltrim($amount, '-');
        $floatAmount = (float) $amount;

        // Convert to integer cents
        $cents = (int) round($floatAmount * 100);

        return [
            'num' => $cents,
            'denom' => 100,
            'is_debit' => $isDebit,
        ];
    }

    /**
     * Parse date string into Y-m-d format.
     */
    protected function parseDate(string $date): string
    {
        $formats = [
            'Y-m-d',
            'd/m/Y',
            'm/d/Y',
            'd-m-Y',
            'Y/m/d',
            'Ymd',
            'd M Y',
            'M d, Y',
        ];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, trim($date));
                if ($parsed) {
                    return $parsed->format('Y-m-d');
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Try Carbon's natural parsing
        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return date('Y-m-d');
        }
    }

    /**
     * Create an import batch.
     */
    public function createBatch(
        Company $company,
        Account $account,
        string $fileName,
        string $fileType,
        array $transactions
    ): ImportBatch {
        return ImportBatch::create([
            'company_id' => $company->id,
            'account_id' => $account->id,
            'file_name' => $fileName,
            'type' => $fileType,
            'status' => 'pending',
            'total_rows' => count($transactions),
            'created_count' => 0,
            'matched_count' => 0,
            'error_count' => 0,
            'errors' => [],
            'mapping' => [],
        ]);
    }

    /**
     * Process an import batch.
     */
    public function processBatch(ImportBatch $batch, array $transactions, Account $offsetAccount): ImportBatch
    {
        $batch->update(['status' => 'processing']);
        $errors = [];

        foreach ($transactions as $index => $txn) {
            try {
                // Check for duplicates
                if ($this->isDuplicate($batch->account_id, $txn)) {
                    $batch->incrementMatched();
                    continue;
                }

                // Create the transaction
                $this->createTransaction($batch, $txn, $offsetAccount);
                $batch->incrementCreated();

            } catch (\Exception $e) {
                $errors[] = [
                    'line' => $txn['line_number'] ?? $index + 1,
                    'error' => $e->getMessage(),
                    'data' => $txn['raw_data'] ?? null,
                ];
                $batch->increment('error_count');
            }
        }

        $batch->update([
            'status' => 'completed',
            'errors' => $errors,
        ]);

        return $batch->fresh();
    }

    /**
     * Check if a transaction is a duplicate.
     */
    protected function isDuplicate(string $accountId, array $txn): bool
    {
        $query = Split::where('account_id', $accountId);

        // Check by reference if available
        if (!empty($txn['reference'])) {
            $exists = $query->clone()
                ->whereHas('transaction', fn ($q) => $q->where('num', $txn['reference']))
                ->exists();
            
            if ($exists) {
                return true;
            }
        }

        // Check by amount and date
        if (isset($txn['date'], $txn['amount_num'], $txn['amount_denom'])) {
            $amountNum = $txn['is_debit'] ? -$txn['amount_num'] : $txn['amount_num'];
            
            $exists = $query->clone()
                ->where('value_num', $amountNum)
                ->where('value_denom', $txn['amount_denom'])
                ->whereHas('transaction', fn ($q) => $q->where('post_date', $txn['date']))
                ->exists();

            if ($exists) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a transaction from imported data.
     */
    protected function createTransaction(ImportBatch $batch, array $txn, Account $offsetAccount): Transaction
    {
        $description = $txn['description'] ?? $txn['memo'] ?? 'Imported transaction';
        $date = $txn['date'] ?? date('Y-m-d');
        $amountNum = $txn['amount_num'];
        $amountDenom = $txn['amount_denom'];

        // For bank imports: positive = deposit, negative = withdrawal
        $isDeposit = !($txn['is_debit'] ?? false);

        $splits = [];

        // Convert to decimal amount for TransactionService
        $decimalAmount = $amountNum / $amountDenom;

        if ($isDeposit) {
            // Deposit: Debit bank account, Credit offset
            $splits[] = [
                'account_id' => $batch->account_id,
                'amount' => $decimalAmount,
                'action' => 'DEBIT',
                'memo' => $description,
            ];
            $splits[] = [
                'account_id' => $offsetAccount->id,
                'amount' => $decimalAmount,
                'action' => 'CREDIT',
                'memo' => $description,
            ];
        } else {
            // Withdrawal: Credit bank account, Debit offset
            $splits[] = [
                'account_id' => $batch->account_id,
                'amount' => $decimalAmount,
                'action' => 'CREDIT',
                'memo' => $description,
            ];
            $splits[] = [
                'account_id' => $offsetAccount->id,
                'amount' => $decimalAmount,
                'action' => 'DEBIT',
                'memo' => $description,
            ];
        }

        return $this->transactionService->create([
            'company_id' => $batch->company_id,
            'transaction_date' => $date,
            'description' => $description,
            'num' => $txn['reference'] ?? null,
        ], $splits);
    }

    /**
     * Match imported transactions with existing ones.
     */
    public function matchTransactions(Account $account, array $transactions): array
    {
        $results = [];

        foreach ($transactions as $txn) {
            $matches = $this->findPotentialMatches($account, $txn);
            
            $results[] = [
                'imported' => $txn,
                'matches' => $matches,
                'status' => count($matches) > 0 ? 'matched' : 'new',
            ];
        }

        return $results;
    }

    /**
     * Find potential matches for an imported transaction.
     */
    protected function findPotentialMatches(Account $account, array $txn): Collection
    {
        if (!isset($txn['date'], $txn['amount_num'])) {
            return collect();
        }

        $amountNum = $txn['is_debit'] ?? false ? -$txn['amount_num'] : $txn['amount_num'];
        $date = Carbon::parse($txn['date']);

        return Split::query()
            ->where('account_id', $account->id)
            ->where('value_num', $amountNum)
            ->where('value_denom', $txn['amount_denom'])
            ->whereHas('transaction', function ($q) use ($date) {
                // Allow 3 days variance
                $q->whereBetween('post_date', [
                    $date->copy()->subDays(3)->format('Y-m-d'),
                    $date->copy()->addDays(3)->format('Y-m-d'),
                ]);
            })
            ->with('transaction')
            ->get()
            ->map(fn ($split) => $split->transaction);
    }

    /**
     * Get import batches for a company.
     */
    public function getBatches(Company $company, ?string $status = null): Collection
    {
        return ImportBatch::query()
            ->where('company_id', $company->id)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with('account')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get CSV column preview.
     */
    public function getCSVPreview(string $content, int $rows = 5): array
    {
        $lines = explode("\n", trim($content));
        $headers = str_getcsv(array_shift($lines));
        $preview = [];

        for ($i = 0; $i < min($rows, count($lines)); $i++) {
            if (empty(trim($lines[$i]))) {
                continue;
            }
            $row = str_getcsv($lines[$i]);
            $preview[] = array_combine($headers, $row);
        }

        return [
            'headers' => $headers,
            'rows' => $preview,
            'total_rows' => count($lines),
        ];
    }
}
