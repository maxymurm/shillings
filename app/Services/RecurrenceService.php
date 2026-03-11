<?php

namespace App\Services;

use App\Models\ScheduledTransaction;
use App\Models\Transaction;
use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RecurrenceService
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Get due scheduled transactions.
     */
    public function getDueTransactions(?Company $company = null): Collection
    {
        $query = ScheduledTransaction::query()
            ->where('is_active', true)
            ->where('is_paused', false)
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->where('next_occurrence', '<=', now());

        if ($company) {
            $query->where('company_id', $company->id);
        }

        // Check max occurrences
        $query->where(function ($q) {
            $q->whereNull('max_occurrences')
                ->orWhereColumn('occurrences_created', '<', 'max_occurrences');
        });

        return $query->with(['template.splits.account'])->get();
    }

    /**
     * Process all due scheduled transactions.
     */
    public function processAllDue(): array
    {
        $scheduled = $this->getDueTransactions();
        $results = [
            'processed' => 0,
            'created' => 0,
            'reminders' => 0,
            'errors' => [],
        ];

        foreach ($scheduled as $scheduledTxn) {
            try {
                if ($scheduledTxn->auto_create) {
                    $this->createFromScheduled($scheduledTxn);
                    $results['created']++;
                } else {
                    // Just a reminder - would trigger notification
                    $results['reminders']++;
                }
                $results['processed']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'id' => $scheduledTxn->id,
                    'name' => $scheduledTxn->name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Create a transaction from a scheduled template.
     */
    public function createFromScheduled(ScheduledTransaction $scheduled): Transaction
    {
        return DB::transaction(function () use ($scheduled) {
            $template = $scheduled->template;
            
            // Handle both template-based and splits_data-based scheduled transactions
            if ($template) {
                // Use template transaction
                $splits = $template->splits->map(fn ($split) => [
                    'account_id' => $split->account_id,
                    'value_num' => $split->value_num,
                    'value_denom' => $split->value_denom,
                    'quantity_num' => $split->quantity_num,
                    'quantity_denom' => $split->quantity_denom,
                    'memo' => $split->memo,
                    'reconcile_state' => 'n',
                ])->toArray();
                
                $currencyCode = $template->currency_code;
                $description = $template->description;
            } elseif ($scheduled->splits_data) {
                // Use splits_data JSON (or array if already decoded via cast)
                $splitsData = is_array($scheduled->splits_data) 
                    ? $scheduled->splits_data 
                    : json_decode($scheduled->splits_data, true);
                    
                if (!$splitsData) {
                    throw new \Exception('Invalid splits_data JSON.');
                }
                
                // Convert splits_data format to transaction splits format
                $splits = [];
                foreach ($splitsData as $splitData) {
                    
                    // Get amount - handle both 'amount' and 'value_num/value_denom' formats
                    if (isset($splitData['amount'])) {
                        $splitAmount = $splitData['amount'];
                    } elseif (isset($splitData['value_num']) && isset($splitData['value_denom'])) {
                        // Already in fraction format - convert to decimal amount for TransactionService
                        $amount = $splitData['value_num'] / $splitData['value_denom'];
                        $splits[] = [
                            'account_id' => $splitData['account_id'],
                            'amount' => $amount,
                            'action' => $splitData['action'],
                            'memo' => $splitData['memo'] ?? null,
                        ];
                        continue;
                    } else {
                        throw new \Exception('Split data must have either amount or value_num/value_denom');
                    }
                    
                    // Convert decimal amount to fraction
                    $money = \App\ValueObjects\Money::fromDecimal(
                        (string) $splitAmount, 
                        $scheduled->currency->code
                    );
                    
                    $splits[] = [
                        'account_id' => $splitData['account_id'],
                        'amount' => $splitAmount,  // TransactionService expects 'amount'
                        'action' => $splitData['action'],
                        'memo' => $splitData['memo'] ?? null,
                    ];
                }
                
                $currencyCode = $scheduled->currency->code;
                $description = $scheduled->description ?? $scheduled->name;
            } else {
                throw new \Exception('No template transaction or splits_data found.');
            }

            $transactionDate = $this->adjustForWeekends($scheduled);

            $transaction = $this->transactionService->create([
                'company_id' => $scheduled->company_id,
                'currency_code' => $currencyCode,
                'date' => $transactionDate,
                'description' => $description,
                'num' => $this->generateTransactionNumber($scheduled),
            ], $splits);

            // Update scheduled transaction
            $scheduled->increment('occurrences_created');
            $scheduled->update([
                'last_occurrence' => now(),
                'last_created_at' => now(),
                'next_occurrence' => $this->calculateNextOccurrence($scheduled),
            ]);

            // Auto-post if configured
            if ($scheduled->auto_post) {
                $this->transactionService->post($transaction);
            }

            return $transaction;
        });
    }

    /**
     * Adjust date for weekend handling.
     */
    protected function adjustForWeekends(ScheduledTransaction $scheduled): Carbon
    {
        $date = $scheduled->next_occurrence instanceof Carbon 
            ? $scheduled->next_occurrence 
            : Carbon::parse($scheduled->next_occurrence);

        if (!$scheduled->skip_weekends) {
            return $date;
        }

        $policy = $scheduled->weekend_policy ?? 'after';

        if ($date->isWeekend()) {
            switch ($policy) {
                case 'before':
                    // Move to previous Friday
                    while ($date->isWeekend()) {
                        $date->subDay();
                    }
                    break;
                case 'after':
                    // Move to next Monday
                    while ($date->isWeekend()) {
                        $date->addDay();
                    }
                    break;
                case 'skip':
                    // Skip this occurrence entirely
                    return $this->calculateNextOccurrence($scheduled);
            }
        }

        return $date;
    }

    /**
     * Calculate the next occurrence date.
     */
    public function calculateNextOccurrence(ScheduledTransaction $scheduled): Carbon
    {
        $current = $scheduled->next_occurrence instanceof Carbon
            ? $scheduled->next_occurrence->copy()
            : Carbon::parse($scheduled->next_occurrence);

        switch ($scheduled->frequency) {
            case 'daily':
                return $current->addDay();
            
            case 'weekly':
                return $current->addWeek();
            
            case 'biweekly':
                return $current->addWeeks(2);
            
            case 'monthly':
                return $current->addMonth();
            
            case 'quarterly':
                return $current->addQuarter();
            
            case 'semi-annually':
                return $current->addMonths(6);
            
            case 'annually':
                return $current->addYear();
            
            case 'once':
                // No next occurrence for one-time scheduled
                return null;
            
            default:
                return $current->addMonth();
        }
    }

    /**
     * Generate a transaction number for scheduled transaction.
     */
    protected function generateTransactionNumber(ScheduledTransaction $scheduled): string
    {
        $prefix = substr($scheduled->name, 0, 3);
        $occurrence = $scheduled->occurrences_created + 1;
        return strtoupper($prefix) . '-' . date('Ymd') . '-' . str_pad($occurrence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new scheduled transaction.
     */
    public function create(array $data, ?Transaction $template = null): ScheduledTransaction
    {
        return DB::transaction(function () use ($data, $template) {
            // Get company from auth if not provided
            if (!isset($data['company_id'])) {
                $data['company_id'] = auth()->user()->currentCompany->id ?? auth()->user()->company_id;
            }

            // Convert splits to JSON
            if (isset($data['splits'])) {
                $data['splits_data'] = json_encode($data['splits']);
                unset($data['splits']);
            }

            $data['template_id'] = $template?->id;
            $data['next_occurrence'] = $this->calculateInitialOccurrence($data);
            $data['occurrences_created'] = 0;

            return ScheduledTransaction::create($data);
        });
    }

    /**
     * Calculate initial occurrence based on frequency and date settings.
     */
    protected function calculateInitialOccurrence(array $data): Carbon
    {
        $start = Carbon::parse($data['start_date']);

        // For monthly, set to specific day of month
        if ($data['frequency'] === 'monthly' && isset($data['day_of_month'])) {
            $start->day = min($data['day_of_month'], $start->daysInMonth);
        }

        // For weekly, set to specific day of week
        if ($data['frequency'] === 'weekly' && isset($data['day_of_week'])) {
            while ($start->dayOfWeek !== $data['day_of_week']) {
                $start->addDay();
            }
        }

        return $start;
    }

    /**
     * Update a scheduled transaction.
     */
    public function update(ScheduledTransaction $scheduled, array $data): ScheduledTransaction
    {
        $scheduled->update($data);
        return $scheduled->fresh();
    }

    /**
     * Skip the next occurrence.
     */
    public function skipNext(ScheduledTransaction $scheduled): ScheduledTransaction
    {
        $scheduled->update([
            'next_occurrence' => $this->calculateNextOccurrence($scheduled),
        ]);

        return $scheduled->fresh();
    }

    /**
     * Get upcoming scheduled transactions.
     */
    public function getUpcoming(Company $company, int $days = 30): Collection
    {
        return ScheduledTransaction::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->where('is_paused', false)
            ->where('next_occurrence', '<=', now()->addDays($days))
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->orderBy('next_occurrence')
            ->get();
    }

    /**
     * Get transactions due for reminders.
     */
    public function getDueForReminders(Company $company): Collection
    {
        return ScheduledTransaction::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->where('is_paused', false)
            ->where('auto_create', false)
            ->get();
    }

    /**
     * End a scheduled transaction series.
     */
    public function endSeries(ScheduledTransaction $scheduled, ?string $reason = null): ScheduledTransaction
    {
        $scheduled->update([
            'is_active' => false,
            'end_date' => now(),
            'description' => $scheduled->description . ($reason ? "\nEnded: {$reason}" : ''),
        ]);

        return $scheduled->fresh();
    }

    /**
     * Get schedule summary for a company.
     */
    public function getSummary(Company $company): array
    {
        $scheduled = ScheduledTransaction::where('company_id', $company->id)->get();

        return [
            'total' => $scheduled->count(),
            'active' => $scheduled->where('is_active', true)->count(),
            'due_today' => $scheduled->where('is_active', true)
                ->where('next_occurrence', '<=', now()->endOfDay())
                ->count(),
            'due_this_week' => $scheduled->where('is_active', true)
                ->where('next_occurrence', '<=', now()->addWeek())
                ->count(),
            'auto_create' => $scheduled->where('is_active', true)
                ->where('auto_create', true)
                ->count(),
        ];
    }

    /**
     * Pause a scheduled transaction.
     */
    public function pause(ScheduledTransaction $scheduled): ScheduledTransaction
    {
        $scheduled->update(['is_paused' => true]);
        return $scheduled->fresh();
    }

    /**
     * Resume a paused scheduled transaction.
     */
    public function resume(ScheduledTransaction $scheduled): ScheduledTransaction
    {
        $scheduled->update(['is_paused' => false]);
        return $scheduled->fresh();
    }
}
