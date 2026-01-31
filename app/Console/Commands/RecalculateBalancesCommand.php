<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Company;
use App\Services\AccountBalanceService;
use Illuminate\Console\Command;

class RecalculateBalancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balances:recalculate
                            {--company= : Recalculate for a specific company ID}
                            {--account= : Recalculate for a specific account ID}
                            {--stale : Only recalculate stale balances}
                            {--all : Recalculate all balances (invalidates first)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate cached account balances';

    public function __construct(
        private AccountBalanceService $balanceService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Account Balance Recalculation');
        $this->newLine();

        // Single account mode
        if ($accountId = $this->option('account')) {
            return $this->recalculateSingleAccount($accountId);
        }

        // Company mode
        if ($companyId = $this->option('company')) {
            return $this->recalculateCompany($companyId);
        }

        // Stale only mode
        if ($this->option('stale')) {
            return $this->recalculateStale();
        }

        // All mode
        if ($this->option('all')) {
            return $this->recalculateAll();
        }

        // Default: show statistics and ask
        $stats = $this->balanceService->getBalanceStatistics();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Accounts', $stats['total_accounts']],
                ['Fresh Accounts', $stats['fresh_accounts']],
                ['Stale Accounts', $stats['stale_accounts']],
                ['Total Splits', $stats['total_splits']],
                ['Last Calculated', $stats['last_calculated'] ?? 'Never'],
            ]
        );

        $this->newLine();

        if ($stats['stale_accounts'] > 0) {
            if ($this->confirm("Recalculate {$stats['stale_accounts']} stale balances?")) {
                return $this->recalculateStale();
            }
        } else {
            $this->info('All balances are up to date!');
        }

        return Command::SUCCESS;
    }

    private function recalculateSingleAccount(string $accountId): int
    {
        $account = Account::find($accountId);

        if (! $account) {
            $this->error("Account not found: {$accountId}");

            return Command::FAILURE;
        }

        $this->info("Recalculating balance for account: {$account->name}");

        $startTime = microtime(true);
        $balance = $this->balanceService->recalculateAccount($account);
        $elapsed = round((microtime(true) - $startTime) * 1000, 2);

        $this->table(
            ['Field', 'Value'],
            [
                ['Balance', number_format($balance->getBalance(), 2)],
                ['Debits', number_format($balance->getDebits(), 2)],
                ['Credits', number_format($balance->getCredits(), 2)],
                ['Split Count', $balance->split_count],
                ['Time', "{$elapsed}ms"],
            ]
        );

        $this->newLine();
        $this->info('✓ Balance recalculated successfully');

        return Command::SUCCESS;
    }

    private function recalculateCompany(string $companyId): int
    {
        $company = Company::find($companyId);

        if (! $company) {
            $this->error("Company not found: {$companyId}");

            return Command::FAILURE;
        }

        $this->info("Recalculating balances for company: {$company->name}");

        $accountCount = Account::where('company_id', $companyId)->count();
        $this->info("Found {$accountCount} accounts");

        $bar = $this->output->createProgressBar($accountCount);
        $bar->start();

        $startTime = microtime(true);
        $accounts = Account::where('company_id', $companyId)->get();

        foreach ($accounts as $account) {
            $this->balanceService->recalculateAccount($account);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->info("✓ Recalculated {$accountCount} accounts in {$elapsed}s");

        return Command::SUCCESS;
    }

    private function recalculateStale(): int
    {
        $this->info('Recalculating stale balances...');

        $startTime = microtime(true);
        $count = $this->balanceService->recalculateStale();
        $elapsed = round(microtime(true) - $startTime, 2);

        $this->info("✓ Recalculated {$count} stale balances in {$elapsed}s");

        return Command::SUCCESS;
    }

    private function recalculateAll(): int
    {
        if (! $this->confirm('This will invalidate and recalculate ALL balances. Continue?')) {
            $this->info('Aborted');

            return Command::SUCCESS;
        }

        $this->info('Invalidating all balances...');
        $invalidated = $this->balanceService->invalidateAll();
        $this->info("Invalidated {$invalidated} balance records");

        $this->newLine();
        $this->info('Recalculating...');

        $startTime = microtime(true);
        $accounts = Account::all();
        $bar = $this->output->createProgressBar($accounts->count());
        $bar->start();

        foreach ($accounts as $account) {
            $this->balanceService->recalculateAccount($account);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->info("✓ Recalculated {$accounts->count()} accounts in {$elapsed}s");

        return Command::SUCCESS;
    }
}
