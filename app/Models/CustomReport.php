<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomReport extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    // Base report types
    public const TYPE_TRIAL_BALANCE = 'trial_balance';
    public const TYPE_BALANCE_SHEET = 'balance_sheet';
    public const TYPE_INCOME_STATEMENT = 'income_statement';
    public const TYPE_CASH_FLOW = 'cash_flow';
    public const TYPE_GENERAL_LEDGER = 'general_ledger';
    public const TYPE_CUSTOM = 'custom';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'created_by_id',
        'name',
        'description',
        'base_report',
        'account_ids',
        'columns',
        'grouping',
        'filters',
        'date_range',
        'is_favorite',
        'is_shared',
        'run_count',
        'last_run_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'account_ids' => 'array',
        'columns' => 'array',
        'grouping' => 'array',
        'filters' => 'array',
        'date_range' => 'array',
        'is_favorite' => 'boolean',
        'is_shared' => 'boolean',
        'run_count' => 'integer',
        'last_run_at' => 'datetime',
    ];

    /**
     * Get available base report types.
     */
    public static function getBaseReportTypes(): array
    {
        return [
            self::TYPE_TRIAL_BALANCE => 'Trial Balance',
            self::TYPE_BALANCE_SHEET => 'Balance Sheet',
            self::TYPE_INCOME_STATEMENT => 'Income Statement',
            self::TYPE_CASH_FLOW => 'Cash Flow Statement',
            self::TYPE_GENERAL_LEDGER => 'General Ledger',
            self::TYPE_CUSTOM => 'Custom Query',
        ];
    }

    /**
     * Get available columns for each report type.
     */
    public static function getAvailableColumns(string $reportType): array
    {
        return match ($reportType) {
            self::TYPE_TRIAL_BALANCE => [
                'account_code' => 'Account Code',
                'account_name' => 'Account Name',
                'account_type' => 'Account Type',
                'debit' => 'Debit',
                'credit' => 'Credit',
                'balance' => 'Balance',
            ],
            self::TYPE_BALANCE_SHEET => [
                'account_code' => 'Account Code',
                'account_name' => 'Account Name',
                'balance' => 'Current Balance',
                'prior_balance' => 'Prior Balance',
                'change' => 'Change',
                'change_percent' => 'Change %',
            ],
            self::TYPE_INCOME_STATEMENT => [
                'account_code' => 'Account Code',
                'account_name' => 'Account Name',
                'current_period' => 'Current Period',
                'prior_period' => 'Prior Period',
                'budget' => 'Budget',
                'variance' => 'Variance',
                'variance_percent' => 'Variance %',
            ],
            self::TYPE_GENERAL_LEDGER => [
                'date' => 'Date',
                'reference' => 'Reference',
                'description' => 'Description',
                'debit' => 'Debit',
                'credit' => 'Credit',
                'running_balance' => 'Running Balance',
            ],
            default => [
                'account_code' => 'Account Code',
                'account_name' => 'Account Name',
                'balance' => 'Balance',
            ],
        };
    }

    /**
     * The company this report belongs to.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The user who created this report.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Record that this report was run.
     */
    public function recordRun(): self
    {
        $this->increment('run_count');
        $this->update(['last_run_at' => now()]);

        return $this;
    }

    /**
     * Toggle favorite status.
     */
    public function toggleFavorite(): self
    {
        $this->is_favorite = !$this->is_favorite;
        $this->save();

        return $this;
    }

    /**
     * Toggle shared status.
     */
    public function toggleShared(): self
    {
        $this->is_shared = !$this->is_shared;
        $this->save();

        return $this;
    }

    /**
     * Duplicate this report.
     */
    public function duplicate(?string $newName = null): self
    {
        $new = $this->replicate(['run_count', 'last_run_at']);
        $new->name = $newName ?? 'Copy of ' . $this->name;
        $new->is_favorite = false;
        $new->created_by_id = auth()->id();
        $new->save();

        return $new;
    }

    /**
     * Scope to get only favorites.
     */
    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    /**
     * Scope to get shared reports.
     */
    public function scopeShared($query)
    {
        return $query->where('is_shared', true);
    }

    /**
     * Scope to order by most used.
     */
    public function scopeMostUsed($query)
    {
        return $query->orderByDesc('run_count');
    }

    /**
     * Scope to order by recently run.
     */
    public function scopeRecentlyRun($query)
    {
        return $query->orderByDesc('last_run_at');
    }
}
