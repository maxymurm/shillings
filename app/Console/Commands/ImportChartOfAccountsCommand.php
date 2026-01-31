<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\ChartOfAccountsService;
use Illuminate\Console\Command;

class ImportChartOfAccountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounts:import
                            {template? : The template key to import (e.g., personal-finance, small-business)}
                            {--company= : The company ID to import into}
                            {--list : List available templates}
                            {--preview : Preview what would be imported without making changes}
                            {--overwrite : Fail if accounts with same code exist (default: skip existing)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import a chart of accounts template into a company';

    public function __construct(
        private ChartOfAccountsService $chartService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // List templates
        if ($this->option('list')) {
            return $this->listTemplates();
        }

        $templateKey = $this->argument('template');

        if (! $templateKey) {
            return $this->selectTemplate();
        }

        return $this->importTemplate($templateKey);
    }

    /**
     * List available templates.
     */
    private function listTemplates(): int
    {
        $this->info('Available Chart of Accounts Templates:');
        $this->newLine();

        $templates = $this->chartService->getAvailableTemplates();

        if (empty($templates)) {
            $this->warn('No templates found in database/templates/');

            return Command::FAILURE;
        }

        $rows = [];
        foreach ($templates as $key => $template) {
            $rows[] = [
                $key,
                $template['name'],
                $template['description'],
                $template['version'],
            ];
        }

        $this->table(
            ['Key', 'Name', 'Description', 'Version'],
            $rows
        );

        $this->newLine();
        $this->info('Usage: php artisan accounts:import <template-key> --company=<company-id>');

        return Command::SUCCESS;
    }

    /**
     * Interactive template selection.
     */
    private function selectTemplate(): int
    {
        $templates = $this->chartService->getAvailableTemplates();

        if (empty($templates)) {
            $this->error('No templates available');

            return Command::FAILURE;
        }

        $choices = [];
        foreach ($templates as $key => $template) {
            $choices[$key] = $template['name'];
        }

        $templateKey = $this->choice(
            'Select a template to import:',
            $choices
        );

        return $this->importTemplate($templateKey);
    }

    /**
     * Import the template.
     */
    private function importTemplate(string $templateKey): int
    {
        // Get template info
        $template = $this->chartService->getTemplate($templateKey);

        if (! $template) {
            $this->error("Template not found: {$templateKey}");
            $this->info('Use --list to see available templates');

            return Command::FAILURE;
        }

        $this->info("Template: {$template['name']}");
        $this->info($template['description'] ?? '');
        $this->newLine();

        // Get company
        $companyId = $this->option('company');

        if (! $companyId) {
            $companies = Company::all();

            if ($companies->isEmpty()) {
                $this->error('No companies found. Create a company first.');

                return Command::FAILURE;
            }

            if ($companies->count() === 1) {
                $company = $companies->first();
                $this->info("Using company: {$company->name}");
            } else {
                $choices = $companies->pluck('name', 'id')->toArray();
                $companyId = $this->choice(
                    'Select a company:',
                    $choices
                );
                $company = Company::find($companyId);
            }
        } else {
            $company = Company::find($companyId);

            if (! $company) {
                $this->error("Company not found: {$companyId}");

                return Command::FAILURE;
            }
        }

        // Preview mode
        if ($this->option('preview')) {
            return $this->previewImport($company, $templateKey);
        }

        // Confirm
        if (! $this->confirm("Import {$template['name']} into {$company->name}?")) {
            $this->info('Aborted');

            return Command::SUCCESS;
        }

        // Import
        $this->info('Importing...');

        try {
            $skipExisting = ! $this->option('overwrite');
            $stats = $this->chartService->importTemplate($company, $templateKey, $skipExisting);

            $this->newLine();
            $this->info('✓ Import completed!');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Accounts Created', $stats['accounts_created']],
                    ['Accounts Skipped', $stats['accounts_skipped']],
                    ['Errors', count($stats['errors'])],
                ]
            );

            if (! empty($stats['errors'])) {
                $this->newLine();
                $this->warn('Errors:');
                foreach ($stats['errors'] as $error) {
                    $this->line("  - {$error}");
                }
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Import failed: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    /**
     * Preview import.
     */
    private function previewImport(Company $company, string $templateKey): int
    {
        $this->info("Preview: What would be imported into {$company->name}");
        $this->newLine();

        $preview = $this->chartService->previewImport($company, $templateKey);

        $this->info("Template: {$preview['template']}");
        $this->info("Total accounts in template: {$preview['total_in_template']}");
        $this->newLine();

        $this->info('New accounts to create: ' . count($preview['new_accounts']));
        if (! empty($preview['new_accounts']) && $this->option('verbose')) {
            foreach ($preview['new_accounts'] as $account) {
                $this->line("  + [{$account['code']}] {$account['name']} ({$account['type']})");
            }
        }

        $this->newLine();
        $this->info('Existing accounts (will be skipped): ' . count($preview['existing_accounts']));
        if (! empty($preview['existing_accounts']) && $this->option('verbose')) {
            foreach ($preview['existing_accounts'] as $account) {
                $this->line("  - [{$account['code']}] {$account['name']}");
            }
        }

        return Command::SUCCESS;
    }
}
