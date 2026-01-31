<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimal_places',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'decimal_places' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Companies that use this currency as default.
     */
    public function companiesUsingAsDefault(): HasMany
    {
        return $this->hasMany(Company::class, 'default_currency_id');
    }

    /**
     * Accounts that use this currency.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Format an amount according to this currency's settings.
     */
    public function formatAmount(float $amount): string
    {
        return number_format($amount, $this->decimal_places) . ' ' . $this->code;
    }

    /**
     * Get the symbol or code for display.
     */
    public function getDisplaySymbolAttribute(): string
    {
        return $this->symbol ?? $this->code;
    }

    /**
     * Check if this is a fiat currency (not cryptocurrency or stock).
     */
    public function isFiat(): bool
    {
        return strlen($this->code) === 3;
    }

    /**
     * Scope to get only active currencies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
