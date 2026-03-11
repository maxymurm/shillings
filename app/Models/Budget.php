<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Budget extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'fiscal_year',
        'num_periods',
        'recurrence',
        'start_date',
        'end_date',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'num_periods' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function budgetAccounts(): HasMany
    {
        return $this->hasMany(BudgetAccount::class);
    }

    /**
     * Get accounts with budgeted amounts (via BudgetAccounts pivot)
     */
    public function accounts()
    {
        return $this->hasManyThrough(
            Account::class,
            BudgetAccount::class,
            'budget_id',
            'id',
            'id',
            'account_id'
        )->distinct();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('fiscal_year', $year);
    }

    // Methods
    public function getAccountBudget(string $accountId, int $period): Money
    {
        $budgetAccount = $this->budgetAccounts()
            ->where('account_id', $accountId)
            ->where('period_num', $period)
            ->first();

        if (!$budgetAccount) {
            return Money::fromFraction(0, 100);
        }

        return Money::fromFraction($budgetAccount->amount_num, $budgetAccount->amount_denom);
    }

    public function setAccountBudget(string $accountId, int $period, Money $amount): BudgetAccount
    {
        return $this->budgetAccounts()->updateOrCreate(
            [
                'account_id' => $accountId,
                'period_num' => $period,
            ],
            [
                'amount_num' => $amount->getNumerator(),
                'amount_denom' => $amount->getDenominator(),
            ]
        );
    }

    public function getTotalBudget(string $accountId): Money
    {
        $total = $this->budgetAccounts()
            ->where('account_id', $accountId)
            ->selectRaw('SUM(amount_num) as total_num, MAX(amount_denom) as total_denom')
            ->first();

        return Money::fromFraction($total->total_num ?? 0, $total->total_denom ?? 100);
    }

    public function getPeriodLabel(int $period): string
    {
        return match ($this->recurrence) {
            'monthly' => Carbon::create($this->fiscal_year, $period)->format('M Y'),
            'quarterly' => 'Q' . $period . ' ' . $this->fiscal_year,
            'yearly' => (string) $this->fiscal_year,
            default => "Period {$period}",
        };
    }

    public function getPeriodStartDate(int $period): Carbon
    {
        return match ($this->recurrence) {
            'monthly' => Carbon::create($this->fiscal_year, $period, 1),
            'quarterly' => Carbon::create($this->fiscal_year, ($period - 1) * 3 + 1, 1),
            'yearly' => Carbon::create($this->fiscal_year, 1, 1),
            default => $this->start_date,
        };
    }

    public function getPeriodEndDate(int $period): Carbon
    {
        return match ($this->recurrence) {
            'monthly' => Carbon::create($this->fiscal_year, $period, 1)->endOfMonth(),
            'quarterly' => Carbon::create($this->fiscal_year, $period * 3, 1)->endOfMonth(),
            'yearly' => Carbon::create($this->fiscal_year, 12, 31),
            default => $this->end_date,
        };
    }

    /**
     * Get period dates as an array with start and end.
     */
    public function getPeriodDates(int $period): array
    {
        return [
            'start' => $this->getPeriodStartDate($period),
            'end' => $this->getPeriodEndDate($period),
        ];
    }

    /**
     * Get the current period number based on today's date.
     */
    public function getCurrentPeriod(): int
    {
        $today = Carbon::today();
        
        return match ($this->recurrence) {
            'monthly' => $today->month,
            'quarterly' => (int) ceil($today->month / 3),
            'yearly' => 1,
            default => 1,
        };
    }

    public function clone(int $newYear): Budget
    {
        $newBudget = $this->replicate();
        $newBudget->fiscal_year = $newYear;
        $newBudget->name = str_replace((string) $this->fiscal_year, (string) $newYear, $this->name);
        $newBudget->start_date = $this->start_date->copy()->year($newYear);
        $newBudget->end_date = $this->end_date->copy()->year($newYear);
        $newBudget->save();

        foreach ($this->budgetAccounts as $budgetAccount) {
            $newBudget->budgetAccounts()->create([
                'account_id' => $budgetAccount->account_id,
                'period_num' => $budgetAccount->period_num,
                'amount_num' => $budgetAccount->amount_num,
                'amount_denom' => $budgetAccount->amount_denom,
            ]);
        }

        return $newBudget;
    }
}
