<?php

namespace App\Models;

use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetAccount extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'budget_id',
        'account_id',
        'period_num',
        'amount_num',
        'amount_denom',
    ];

    protected $casts = [
        'period_num' => 'integer',
        'amount_num' => 'integer',
        'amount_denom' => 'integer',
    ];

    // Relationships
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    // Accessors
    public function getAmountAttribute(): Money
    {
        return Money::fromFraction($this->amount_num, $this->amount_denom);
    }

    // Mutators
    public function setAmountAttribute(Money $value): void
    {
        $this->attributes['amount_num'] = $value->getNumerator();
        $this->attributes['amount_denom'] = $value->getDenominator();
    }
}
