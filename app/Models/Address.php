<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Address extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'addressable_type',
        'addressable_id',
        'type',
        'street',
        'city',
        'state',
        'zip_code',
        'country',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeBilling($query)
    {
        return $query->where('type', 'billing');
    }

    public function scopeShipping($query)
    {
        return $query->where('type', 'shipping');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street,
            $this->city,
            $this->state,
            $this->zip_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    public function getFormattedAddressAttribute(): string
    {
        $lines = [];
        
        if ($this->street) {
            $lines[] = $this->street;
        }
        
        $cityLine = array_filter([$this->city, $this->state, $this->zip_code]);
        if (!empty($cityLine)) {
            $lines[] = implode(', ', $cityLine);
        }
        
        if ($this->country) {
            $lines[] = $this->country;
        }
        
        return implode("\n", $lines);
    }
}
