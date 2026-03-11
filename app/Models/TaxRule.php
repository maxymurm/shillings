<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxRule extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'tax_id',
        'applies_to',
        'account_id',
        'contact_type',
        'region',
        'priority',
    ];

    protected $casts = [
        'priority' => 'integer',
    ];

    // Relationships
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    // Scopes
    public function scopeForSales($query)
    {
        return $query->whereIn('applies_to', ['sales', 'both']);
    }

    public function scopeForPurchases($query)
    {
        return $query->whereIn('applies_to', ['purchases', 'both']);
    }

    public function scopeForContactType($query, string $type)
    {
        return $query->where(function ($q) use ($type) {
            $q->where('contact_type', $type)
              ->orWhereNull('contact_type');
        });
    }

    public function scopeForRegion($query, ?string $region)
    {
        return $query->where(function ($q) use ($region) {
            $q->where('region', $region)
              ->orWhereNull('region');
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}
