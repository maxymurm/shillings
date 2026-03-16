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

class Transaction extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'currency_id',
        'transaction_date',
        'post_date',
        'description',
        'reference',
        'notes',
        'is_posted',
        'posted_at',
        'is_void',
        'void_reason',
        'voided_at',
        'voided_by_id',
        'receipt_path',
        'created_by_id',
        'reverses_id',
        'reversed_by_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'transaction_date' => 'date',
        'post_date' => 'date',
        'is_posted' => 'boolean',
        'is_void' => 'boolean',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    /**
     * The company this transaction belongs to.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The currency for this transaction.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * The user who created this transaction.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * The user who voided this transaction.
     */
    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by_id');
    }

    /**
     * The transaction this reverses (if this is a reversal).
     */
    public function reverses(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'reverses_id');
    }

    /**
     * The transaction that reversed this one.
     */
    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'reversed_by_id');
    }

    /**
     * The splits (entries) for this transaction.
     */
    public function splits(): HasMany
    {
        return $this->hasMany(Split::class);
    }

    /**
     * Check if this transaction is balanced (debits = credits).
     */
    public function isBalanced(): bool
    {
        $total = Money::zero();

        foreach ($this->splits as $split) {
            $amount = Money::fromFraction($split->amount_num, $split->amount_denom);
            $total = $total->add($amount);
        }

        return $total->isZero();
    }

    /**
     * Get the total amount of the transaction (sum of all positive splits).
     */
    public function getTotal(): Money
    {
        $total = Money::zero($this->currency?->code);

        foreach ($this->splits as $split) {
            $amount = Money::fromFraction($split->amount_num, $split->amount_denom, $this->currency?->code);
            if ($amount->isPositive()) {
                $total = $total->add($amount);
            }
        }

        return $total;
    }

    /**
     * Check if transaction can be edited.
     */
    public function canEdit(): bool
    {
        return ! $this->is_posted && ! $this->is_void;
    }

    /**
     * Check if transaction can be deleted.
     */
    public function canDelete(): bool
    {
        return ! $this->is_posted && ! $this->is_void;
    }

    /**
     * Check if transaction can be posted.
     */
    public function canPost(): bool
    {
        return ! $this->is_posted && ! $this->is_void && $this->isBalanced();
    }

    /**
     * Check if transaction can be reversed.
     */
    public function canReverse(): bool
    {
        return $this->is_posted && ! $this->is_void && ! $this->reversed_by_id;
    }

    /**
     * Check if transaction can be voided.
     */
    public function canVoid(): bool
    {
        return $this->is_posted && ! $this->is_void && ! $this->reversed_by_id;
    }

    /**
     * Check if this is a reversal transaction.
     */
    public function isReversal(): bool
    {
        return $this->reverses_id !== null;
    }

    /**
     * Check if this transaction has been reversed.
     */
    public function isReversed(): bool
    {
        return $this->reversed_by_id !== null;
    }

    /**
     * Get transaction status.
     */
    public function getStatus(): string
    {
        if ($this->is_void) {
            return 'void';
        }
        if ($this->reversed_by_id) {
            return 'reversed';
        }
        if ($this->is_posted) {
            return 'posted';
        }

        return 'draft';
    }

    /**
     * Scope to get only posted transactions.
     */
    public function scopePosted($query)
    {
        return $query->where('is_posted', true);
    }

    /**
     * Scope to get only draft (unposted) transactions.
     */
    public function scopeDraft($query)
    {
        return $query->where('is_posted', false);
    }

    /**
     * Scope to exclude voided transactions.
     */
    public function scopeNotVoid($query)
    {
        return $query->where('is_void', false);
    }

    /**
     * Scope to exclude reversed transactions.
     */
    public function scopeNotReversed($query)
    {
        return $query->whereNull('reversed_by_id');
    }

    /**
     * Scope to get only effective transactions (posted, not void, not reversed).
     */
    public function scopeEffective($query)
    {
        return $query->posted()->notVoid()->notReversed();
    }

    /**
     * Scope to get transactions on or after a date.
     */
    public function scopeFromDate($query, $date)
    {
        return $query->where('post_date', '>=', $date);
    }

    /**
     * Scope to get transactions on or before a date.
     */
    public function scopeToDate($query, $date)
    {
        return $query->where('post_date', '<=', $date);
    }

    /**
     * Scope to get transactions in a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->fromDate($startDate)->toDate($endDate);
    }
}
