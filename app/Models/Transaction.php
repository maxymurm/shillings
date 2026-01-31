<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
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
        'transaction_date',
        'post_date',
        'description',
        'num',
        'notes',
        'is_posted',
        'created_by',
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
    ];

    /**
     * The company this transaction belongs to.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The user who created this transaction.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
        $debits = $this->splits()
            ->where('action', Split::DEBIT)
            ->sum('value_num');

        $credits = $this->splits()
            ->where('action', Split::CREDIT)
            ->sum('value_num');

        return $debits === $credits;
    }

    /**
     * Get the total amount of the transaction (sum of all debits).
     */
    public function getTotal(): float
    {
        $totalNum = $this->splits()
            ->where('action', Split::DEBIT)
            ->sum('value_num');

        $totalDenom = $this->splits()
            ->where('action', Split::DEBIT)
            ->first()?->value_denom ?? 100;

        return $totalNum / $totalDenom;
    }

    /**
     * Mark the transaction as posted (immutable).
     */
    public function post(): self
    {
        if (! $this->isBalanced()) {
            throw new \RuntimeException('Cannot post an unbalanced transaction');
        }

        $this->is_posted = true;
        $this->post_date = now();
        $this->save();

        return $this;
    }

    /**
     * Create a reversing entry for this transaction.
     */
    public function reverse(string $description = null): Transaction
    {
        $reversal = $this->replicate([
            'is_posted',
            'post_date',
            'created_at',
            'updated_at',
        ]);

        $reversal->description = $description ?? "Reversal of: {$this->description}";
        $reversal->transaction_date = now();
        $reversal->is_posted = false;
        $reversal->save();

        // Create reversed splits
        foreach ($this->splits as $split) {
            $reversal->splits()->create([
                'account_id' => $split->account_id,
                'amount_num' => $split->amount_num,
                'amount_denom' => $split->amount_denom,
                'value_num' => $split->value_num,
                'value_denom' => $split->value_denom,
                'action' => $split->action === Split::DEBIT ? Split::CREDIT : Split::DEBIT,
                'memo' => "Reversal: {$split->memo}",
            ]);
        }

        return $reversal;
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
     * Scope to get transactions on or after a date.
     */
    public function scopeFromDate($query, $date)
    {
        return $query->where('transaction_date', '>=', $date);
    }

    /**
     * Scope to get transactions on or before a date.
     */
    public function scopeToDate($query, $date)
    {
        return $query->where('transaction_date', '<=', $date);
    }

    /**
     * Scope to get transactions in a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->fromDate($startDate)->toDate($endDate);
    }
}
