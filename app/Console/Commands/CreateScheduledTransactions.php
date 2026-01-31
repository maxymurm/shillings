<?php

namespace App\Console\Commands;

use App\Models\ScheduledTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreateScheduledTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:create-scheduled 
                            {--dry-run : Preview without creating transactions}
                            {--company= : Process only a specific company}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create transactions from scheduled/recurring entries that are due';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $companyId = $this->option('company');

        $this->info('Processing scheduled transactions...');

        $query = ScheduledTransaction::query()
            ->due()
            ->autoCreate()
            ->with(['company', 'currency']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $schedules = $query->get();

        if ($schedules->isEmpty()) {
            $this->info('No scheduled transactions are due.');
            return Command::SUCCESS;
        }

        $this->info("Found {$schedules->count()} scheduled transaction(s) due.");

        $created = 0;
        $errors = 0;

        foreach ($schedules as $schedule) {
            $this->line("Processing: {$schedule->name} (Company: {$schedule->company->name})");

            if ($dryRun) {
                $this->info("  [DRY-RUN] Would create transaction for {$schedule->next_occurrence->format('Y-m-d')}");
                continue;
            }

            try {
                $transaction = $schedule->createTransaction();

                if ($transaction) {
                    $created++;
                    $status = $transaction->is_posted ? 'posted' : 'draft';
                    $this->info("  Created {$status} transaction: {$transaction->description}");
                    
                    Log::info('Scheduled transaction created', [
                        'schedule_id' => $schedule->id,
                        'transaction_id' => $transaction->id,
                        'company_id' => $schedule->company_id,
                    ]);
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("  Failed: {$e->getMessage()}");
                
                Log::error('Failed to create scheduled transaction', [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Summary: {$created} created, {$errors} errors");

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
