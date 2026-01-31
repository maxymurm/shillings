<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduledTransaction extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    // Frequency constants
    public const FREQ_DAILY = 'daily';
    public const FREQ_WEEKLY = 'weekly';
    public const FREQ_BI_WEEKLY = 'bi-weekly';
    public const FREQ_MONTHLY = 'monthly';
    public const FREQ_QUARTERLY = 'quarterly';
    public const FREQ_YEARLY = 'yearly';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'template_id',
        'created_by_id',
        'name',
        'description',
        'currency_id',
        'splits_data',
        'frequency',
        'frequency_interval',
        'start_date',
        'end_date',
        'next_occurrence',
        'last_occurrence',
        'day_of_week',
        'day_of_month',
        'month_of_year',
        'auto_create',
        'auto_post',
        'is_active',
        'is_paused',
        'occurrences_created',
        'max_occurrences',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'splits_data' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_occurrence' => 'date',
        'last_occurrence' => 'date',
        'frequency_interval' => 'integer',
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
        'month_of_year' => 'integer',
        'auto_create' => 'boolean',
        'auto_post' => 'boolean',
        'is_active' => 'boolean',
        'is_paused' => 'boolean',
        'occurrences_created' => 'integer',
        'max_occurrences' => 'integer',
    ];

    /**
     * Get available frequencies.
     */
    public static function getFrequencies(): array
    {
        return [
            self::FREQ_DAILY => 'Daily',
            self::FREQ_WEEKLY => 'Weekly',
            self::FREQ_BI_WEEKLY => 'Bi-Weekly',
            self::FREQ_MONTHLY => 'Monthly',
            self::FREQ_QUARTERLY => 'Quarterly',
            self::FREQ_YEARLY => 'Yearly',
        ];
    }

    /**
     * The company this schedule belongs to.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The template this schedule is based on.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(TransactionTemplate::class);
    }

    /**
     * The user who created this schedule.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * The currency for transactions.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Check if the schedule is due.
     */
    public function isDue(): bool
    {
        if (!$this->is_active || $this->is_paused) {
            return false;
        }

        if ($this->end_date && $this->next_occurrence->gt($this->end_date)) {
            return false;
        }

        if ($this->max_occurrences && $this->occurrences_created >= $this->max_occurrences) {
            return false;
        }

        return $this->next_occurrence->lte(now());
    }

    /**
     * Create a transaction from this schedule.
     */
    public function createTransaction(): ?Transaction
    {
        if (!$this->isDue()) {
            return null;
        }

        $transaction = Transaction::create([
            'company_id' => $this->company_id,
            'currency_id' => $this->currency_id,
            'transaction_date' => $this->next_occurrence,
            'description' => $this->description ?? $this->name,
            'created_by_id' => $this->created_by_id,
            'is_posted' => false,
        ]);

        foreach ($this->splits_data as $splitData) {
            Split::create([
                'transaction_id' => $transaction->id,
                'account_id' => $splitData['account_id'],
                'amount_num' => (int) round(($splitData['amount'] ?? 0) * 100),
                'amount_denom' => 100,
                'value_num' => (int) round(($splitData['amount'] ?? 0) * 100),
                'value_denom' => 100,
                'action' => $splitData['action'] ?? Split::DEBIT,
                'memo' => $splitData['memo'] ?? null,
            ]);
        }

        // Auto-post if configured
        if ($this->auto_post && $transaction->isBalanced()) {
            $transaction->is_posted = true;
            $transaction->posted_at = now();
            $transaction->post_date = $transaction->transaction_date;
            $transaction->save();
        }

        // Update schedule
        $this->last_occurrence = $this->next_occurrence;
        $this->occurrences_created++;
        $this->next_occurrence = $this->calculateNextOccurrence();
        $this->save();

        return $transaction->load('splits.account');
    }

    /**
     * Calculate the next occurrence date.
     */
    public function calculateNextOccurrence(?Carbon $from = null): Carbon
    {
        $from = $from ?? $this->next_occurrence ?? $this->start_date;
        $interval = $this->frequency_interval;

        return match ($this->frequency) {
            self::FREQ_DAILY => $from->copy()->addDays($interval),
            self::FREQ_WEEKLY => $from->copy()->addWeeks($interval),
            self::FREQ_BI_WEEKLY => $from->copy()->addWeeks(2 * $interval),
            self::FREQ_MONTHLY => $this->getNextMonthlyOccurrence($from, $interval),
            self::FREQ_QUARTERLY => $from->copy()->addMonths(3 * $interval),
            self::FREQ_YEARLY => $this->getNextYearlyOccurrence($from, $interval),
            default => $from->copy()->addDays($interval),
        };
    }

    /**
     * Get next monthly occurrence respecting day_of_month.
     */
    protected function getNextMonthlyOccurrence(Carbon $from, int $interval): Carbon
    {
        $next = $from->copy()->addMonths($interval);

        if ($this->day_of_month) {
            $day = min($this->day_of_month, $next->daysInMonth);
            $next->setDay($day);
        }

        return $next;
    }

    /**
     * Get next yearly occurrence respecting month_of_year and day_of_month.
     */
    protected function getNextYearlyOccurrence(Carbon $from, int $interval): Carbon
    {
        $next = $from->copy()->addYears($interval);

        if ($this->month_of_year) {
            $next->setMonth($this->month_of_year);
        }

        if ($this->day_of_month) {
            $day = min($this->day_of_month, $next->daysInMonth);
            $next->setDay($day);
        }

        return $next;
    }

    /**
     * Pause the schedule.
     */
    public function pause(): self
    {
        $this->is_paused = true;
        $this->save();

        return $this;
    }

    /**
     * Resume the schedule.
     */
    public function resume(): self
    {
        $this->is_paused = false;
        $this->save();

        return $this;
    }

    /**
     * Skip the next occurrence.
     */
    public function skip(): self
    {
        $this->next_occurrence = $this->calculateNextOccurrence();
        $this->save();

        return $this;
    }

    /**
     * Deactivate the schedule.
     */
    public function deactivate(): self
    {
        $this->is_active = false;
        $this->save();

        return $this;
    }

    /**
     * Scope to get only active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_paused', false);
    }

    /**
     * Scope to get schedules due on or before a date.
     */
    public function scopeDue($query, ?Carbon $date = null)
    {
        $date = $date ?? now();

        return $query->active()
            ->where('next_occurrence', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            })
            ->where(function ($q) {
                $q->whereNull('max_occurrences')
                    ->orWhereColumn('occurrences_created', '<', 'max_occurrences');
            });
    }

    /**
     * Scope to get auto-create schedules.
     */
    public function scopeAutoCreate($query)
    {
        return $query->where('auto_create', true);
    }
}
