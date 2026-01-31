<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionTemplate extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'created_by_id',
        'name',
        'description',
        'currency_id',
        'splits_data',
        'is_favorite',
        'use_count',
        'last_used_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'splits_data' => 'array',
        'is_favorite' => 'boolean',
        'use_count' => 'integer',
        'last_used_at' => 'datetime',
    ];

    /**
     * The company this template belongs to.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The user who created this template.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * The currency for this template.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Record that this template was used.
     */
    public function recordUsage(): self
    {
        $this->increment('use_count');
        $this->update(['last_used_at' => now()]);

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
     * Create a transaction from this template.
     */
    public function createTransaction(?string $description = null, ?\Carbon\Carbon $date = null): Transaction
    {
        $transaction = Transaction::create([
            'company_id' => $this->company_id,
            'currency_id' => $this->currency_id,
            'transaction_date' => $date ?? now(),
            'description' => $description ?? $this->name,
            'created_by_id' => auth()->id(),
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

        $this->recordUsage();

        return $transaction->load('splits.account');
    }

    /**
     * Create a template from an existing transaction.
     */
    public static function createFromTransaction(Transaction $transaction, string $name, ?string $description = null): self
    {
        $splitsData = $transaction->splits->map(function ($split) {
            return [
                'account_id' => $split->account_id,
                'amount' => $split->amount,
                'action' => $split->action,
                'memo' => $split->memo,
            ];
        })->toArray();

        return static::create([
            'company_id' => $transaction->company_id,
            'created_by_id' => auth()->id(),
            'name' => $name,
            'description' => $description ?? $transaction->description,
            'currency_id' => $transaction->currency_id,
            'splits_data' => $splitsData,
        ]);
    }

    /**
     * Scope to get only favorites.
     */
    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    /**
     * Scope to order by most used.
     */
    public function scopeMostUsed($query)
    {
        return $query->orderByDesc('use_count');
    }

    /**
     * Scope to order by recently used.
     */
    public function scopeRecentlyUsed($query)
    {
        return $query->orderByDesc('last_used_at');
    }
}
