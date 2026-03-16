<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Imports a GnuCash "Export Accounts" CSV into the Shillings chart of accounts.
 *
 * GnuCash CSV columns (File → Export → Export Accounts):
 *   type, full_name, name, code, description, color, notes,
 *   commodities, hidden, tax_info, placeholder
 *
 * The `full_name` column encodes hierarchy with ":" separators,
 * e.g. "Assets:Current Assets:Cash".
 */
class GnuCashImportService
{
    /**
     * Maps every GnuCash account type to one of the five Shillings types.
     */
    private const TYPE_MAP = [
        'ASSET'      => 'ASSET',
        'BANK'       => 'ASSET',
        'CASH'       => 'ASSET',
        'CREDIT'     => 'LIABILITY',   // credit-card account
        'EQUITY'     => 'EQUITY',
        'EXPENSE'    => 'EXPENSE',
        'INCOME'     => 'INCOME',
        'LIABILITY'  => 'LIABILITY',
        'MUTUAL'     => 'ASSET',
        'RECEIVABLE' => 'ASSET',
        'PAYABLE'    => 'LIABILITY',
        'STOCK'      => 'ASSET',
        'TRADING'    => 'EQUITY',
    ];

    /**
     * Import accounts from a GnuCash CSV export.
     *
     * @param  string   $csvContent   Raw CSV text
     * @param  Company  $company      Target company
     * @param  bool     $skipExisting Skip accounts whose code already exists
     * @return array{created: int, skipped: int, errors: string[]}
     */
    public function import(string $csvContent, Company $company, bool $skipExisting = true): array
    {
        $rows = $this->parseCsv($csvContent);

        if (empty($rows)) {
            return [
                'created' => 0,
                'skipped' => 0,
                'errors'  => ['No valid rows found. Make sure this is a GnuCash "Export Accounts" CSV file.'],
            ];
        }

        $accountTypes      = AccountType::pluck('id', 'name')->toArray();
        $defaultCurrencyId = $company->default_currency_id;

        $stats      = ['created' => 0, 'skipped' => 0, 'errors' => []];
        $createdMap = []; // full_name => Account::id

        // Pre-load existing account codes so we can skip duplicates efficiently
        $existingCodes = Account::where('company_id', $company->id)
            ->whereNotNull('code')
            ->pluck('id', 'code')
            ->toArray();

        DB::transaction(function () use (
            $rows,
            $company,
            $accountTypes,
            $defaultCurrencyId,
            $skipExisting,
            &$stats,
            &$createdMap,
            &$existingCodes
        ) {
            // Process shallowest accounts first (fewer colons = higher in hierarchy)
            usort($rows, fn ($a, $b) =>
                substr_count($a['full_name'], ':') <=> substr_count($b['full_name'], ':')
            );

            foreach ($rows as $row) {
                $gnuType = strtoupper(trim($row['type'] ?? ''));

                // ROOT accounts are the invisible top-level node — always skip
                if ($gnuType === 'ROOT' || $gnuType === '') {
                    $stats['skipped']++;
                    continue;
                }

                if (! isset(self::TYPE_MAP[$gnuType])) {
                    $stats['errors'][] = "Unknown GnuCash type '{$gnuType}' — skipped: {$row['name']}";
                    $stats['skipped']++;
                    continue;
                }

                $ourTypeName = self::TYPE_MAP[$gnuType];
                $typeId      = $accountTypes[$ourTypeName] ?? null;

                if (! $typeId) {
                    $stats['errors'][] = "Account type '{$ourTypeName}' not configured — skipped: {$row['name']}";
                    $stats['skipped']++;
                    continue;
                }

                $name = trim($row['name'] ?? '');
                $code = trim($row['code'] ?? '') ?: null;

                if ($name === '') {
                    $stats['skipped']++;
                    continue;
                }

                // Skip if a code match already exists in this company
                if ($skipExisting && $code !== null && isset($existingCodes[$code])) {
                    $stats['skipped']++;
                    // Still register in the map so children resolve correctly
                    $createdMap[$row['full_name']] = $existingCodes[$code];
                    continue;
                }

                $parentId     = $this->resolveParent($row['full_name'], $createdMap);
                $isPlaceholder = strtoupper(trim($row['placeholder'] ?? '')) === 'T';
                $isHidden      = strtoupper(trim($row['hidden']      ?? '')) === 'T';

                try {
                    $account = Account::create([
                        'company_id'      => $company->id,
                        'account_type_id' => $typeId,
                        'currency_id'     => $defaultCurrencyId,
                        'parent_id'       => $parentId,
                        'code'            => $code,
                        'name'            => $name,
                        'description'     => trim($row['description'] ?? '') ?: null,
                        'is_placeholder'  => $isPlaceholder,
                        'is_hidden'       => $isHidden,
                    ]);

                    $createdMap[$row['full_name']] = $account->id;

                    if ($code !== null) {
                        $existingCodes[$code] = $account->id;
                    }

                    $stats['created']++;
                } catch (\Throwable $e) {
                    Log::warning('GnuCash import: failed to create account', [
                        'name'  => $name,
                        'error' => $e->getMessage(),
                    ]);
                    $stats['errors'][] = "Could not create '{$name}': " . $e->getMessage();
                    $stats['skipped']++;
                }
            }
        });

        return $stats;
    }

    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Parse the GnuCash CSV text into an array of normalised rows.
     *
     * Handles both standard column names and the alternate labels some
     * GnuCash versions produce (e.g. "Full Account Name" vs "full_name").
     */
    private function parseCsv(string $content): array
    {
        $content = str_replace(["\r\n", "\r"], "\n", trim($content));
        $lines   = explode("\n", $content);

        if (count($lines) < 2) {
            return [];
        }

        $headers = array_map(
            fn ($h) => strtolower(trim($h, " \t\r\n\0\x0B\"")),
            str_getcsv(array_shift($lines))
        );

        $rows = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $values = str_getcsv($line);

            // Pad short rows so array_combine always succeeds
            while (count($values) < count($headers)) {
                $values[] = '';
            }

            $row = array_combine($headers, array_slice($values, 0, count($headers)));

            $rows[] = $this->normaliseKeys($row);
        }

        return $rows;
    }

    /**
     * Map alternate GnuCash column name variants to canonical keys.
     */
    private function normaliseKeys(array $row): array
    {
        static $aliases = [
            'account type'      => 'type',
            'account_type'      => 'type',
            'full account name' => 'full_name',
            'account name'      => 'name',
            'account_name'      => 'name',
            'account code'      => 'code',
            'account_code'      => 'code',
            'notes'             => 'description',
        ];

        $normalised = [];
        foreach ($row as $key => $value) {
            $normalised[$aliases[$key] ?? $key] = $value;
        }

        return $normalised;
    }

    /**
     * Determine the parent account ID for the given full_name path.
     *
     * GnuCash paths look like "Assets:Current Assets:Cash".
     * We strip the last segment and look up the parent in $createdMap.
     * Accounts at depth 1 (directly under Root) have no parent in our system.
     */
    private function resolveParent(string $fullName, array $createdMap): ?string
    {
        $parts = explode(':', $fullName);

        // Depth 0 = Root itself, depth 1 = top-level account → no parent
        if (count($parts) <= 2) {
            return null;
        }

        array_pop($parts);
        $parentPath = implode(':', $parts);

        return $createdMap[$parentPath] ?? null;
    }
}
