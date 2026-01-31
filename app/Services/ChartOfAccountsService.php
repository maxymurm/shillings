<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

/**
 * Chart of Accounts Template Service
 *
 * Imports predefined chart of accounts templates into a company.
 */
class ChartOfAccountsService
{
    /**
     * Available templates.
     */
    private const TEMPLATES = [
        'personal-finance' => 'Personal Finance',
        'small-business' => 'Small Business (General)',
        'retail-business' => 'Retail Business',
        'service-business' => 'Service Business',
        'non-profit' => 'Non-Profit Organization',
    ];

    /**
     * Get list of available templates.
     */
    public function getAvailableTemplates(): array
    {
        $templates = [];

        foreach (self::TEMPLATES as $key => $name) {
            $path = database_path("templates/{$key}.json");
            if (File::exists($path)) {
                $data = json_decode(File::get($path), true);
                $templates[$key] = [
                    'key' => $key,
                    'name' => $data['name'] ?? $name,
                    'description' => $data['description'] ?? '',
                    'version' => $data['version'] ?? '1.0',
                    'locale' => $data['locale'] ?? 'en',
                ];
            }
        }

        return $templates;
    }

    /**
     * Get template data by key.
     */
    public function getTemplate(string $key): ?array
    {
        $path = database_path("templates/{$key}.json");

        if (! File::exists($path)) {
            return null;
        }

        return json_decode(File::get($path), true);
    }

    /**
     * Import a template into a company.
     *
     * @throws InvalidArgumentException
     */
    public function importTemplate(
        Company $company,
        string $templateKey,
        bool $skipExisting = true
    ): array {
        $template = $this->getTemplate($templateKey);

        if (! $template) {
            throw new InvalidArgumentException("Template not found: {$templateKey}");
        }

        $stats = [
            'template' => $template['name'],
            'accounts_created' => 0,
            'accounts_skipped' => 0,
            'errors' => [],
        ];

        // Get account types mapping
        $accountTypes = AccountType::pluck('id', 'name')->toArray();

        DB::transaction(function () use ($company, $template, $accountTypes, $skipExisting, &$stats) {
            foreach ($template['accounts'] as $accountData) {
                $this->importAccount(
                    $company,
                    $accountData,
                    null,
                    $accountTypes,
                    $skipExisting,
                    $stats
                );
            }
        });

        return $stats;
    }

    /**
     * Import a single account and its children recursively.
     */
    private function importAccount(
        Company $company,
        array $data,
        ?Account $parent,
        array $accountTypes,
        bool $skipExisting,
        array &$stats
    ): ?Account {
        // Check if account with this code already exists
        $existing = Account::where('company_id', $company->id)
            ->where('code', $data['code'])
            ->first();

        if ($existing) {
            if ($skipExisting) {
                $stats['accounts_skipped']++;

                // Still need to process children
                if (isset($data['children'])) {
                    foreach ($data['children'] as $childData) {
                        $this->importAccount(
                            $company,
                            $childData,
                            $existing, // Use existing as parent
                            $accountTypes,
                            $skipExisting,
                            $stats
                        );
                    }
                }

                return $existing;
            } else {
                $stats['errors'][] = "Account code {$data['code']} already exists";

                return null;
            }
        }

        // Map account type
        $typeName = $this->mapAccountType($data['type']);
        $typeId = $accountTypes[$typeName] ?? null;

        if (! $typeId) {
            $stats['errors'][] = "Unknown account type: {$data['type']} for account {$data['code']}";

            return null;
        }

        // Create the account
        $account = Account::create([
            'company_id' => $company->id,
            'account_type_id' => $typeId,
            'parent_id' => $parent?->id,
            'code' => $data['code'],
            'name' => $data['name'],
            'currency_id' => $company->default_currency_id,
            'is_placeholder' => $data['placeholder'] ?? false,
            'is_hidden' => false,
            'description' => $data['description'] ?? null,
        ]);

        $stats['accounts_created']++;

        // Process children
        if (isset($data['children'])) {
            foreach ($data['children'] as $childData) {
                $this->importAccount(
                    $company,
                    $childData,
                    $account,
                    $accountTypes,
                    $skipExisting,
                    $stats
                );
            }
        }

        return $account;
    }

    /**
     * Map template type to database type.
     */
    private function mapAccountType(string $type): string
    {
        return match (strtoupper($type)) {
            'ASSET' => 'Asset',
            'LIABILITY' => 'Liability',
            'EQUITY' => 'Equity',
            'INCOME', 'REVENUE' => 'Income',
            'EXPENSE' => 'Expense',
            default => 'Asset',
        };
    }

    /**
     * Preview what would be imported (dry run).
     */
    public function previewImport(Company $company, string $templateKey): array
    {
        $template = $this->getTemplate($templateKey);

        if (! $template) {
            throw new InvalidArgumentException("Template not found: {$templateKey}");
        }

        $preview = [
            'template' => $template['name'],
            'existing_accounts' => [],
            'new_accounts' => [],
            'total_in_template' => 0,
        ];

        $existingCodes = Account::where('company_id', $company->id)
            ->pluck('code')
            ->toArray();

        $this->previewAccounts($template['accounts'], $existingCodes, $preview);

        return $preview;
    }

    /**
     * Recursively preview accounts.
     */
    private function previewAccounts(array $accounts, array $existingCodes, array &$preview): void
    {
        foreach ($accounts as $account) {
            $preview['total_in_template']++;

            if (in_array($account['code'], $existingCodes)) {
                $preview['existing_accounts'][] = [
                    'code' => $account['code'],
                    'name' => $account['name'],
                ];
            } else {
                $preview['new_accounts'][] = [
                    'code' => $account['code'],
                    'name' => $account['name'],
                    'type' => $account['type'],
                ];
            }

            if (isset($account['children'])) {
                $this->previewAccounts($account['children'], $existingCodes, $preview);
            }
        }
    }
}
