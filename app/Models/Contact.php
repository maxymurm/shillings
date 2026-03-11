<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'email',
        'phone',
        'tax_number',
        'website',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'currency_code',
        'reference',
        'enabled',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function persons(): HasMany
    {
        return $this->hasMany(ContactPerson::class);
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Document::class)->where('type', 'invoice');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Document::class)->where('type', 'bill');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeCustomers($query)
    {
        return $query->where('type', 'customer');
    }

    public function scopeVendors($query)
    {
        return $query->where('type', 'vendor');
    }

    public function scopeEmployees($query)
    {
        return $query->where('type', 'employee');
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    // Accessors
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->zip_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->reference ? "{$this->name} ({$this->reference})" : $this->name;
    }

    public function getPrimaryPersonAttribute(): ?ContactPerson
    {
        return $this->persons()->where('is_primary', true)->first();
    }

    public function getBillingAddressAttribute(): ?Address
    {
        return $this->addresses()->where('type', 'billing')->first()
            ?? $this->addresses()->where('is_default', true)->first();
    }

    public function getShippingAddressAttribute(): ?Address
    {
        return $this->addresses()->where('type', 'shipping')->first()
            ?? $this->billing_address;
    }

    // Methods
    public function getReceivableBalance(): Money
    {
        $total = $this->invoices()
            ->whereIn('status', ['sent', 'viewed', 'partial', 'overdue'])
            ->selectRaw('SUM(total_num) as total_num, MAX(total_denom) as total_denom')
            ->first();

        $paid = $this->invoices()
            ->whereIn('status', ['sent', 'viewed', 'partial', 'overdue'])
            ->selectRaw('SUM(amount_paid_num) as paid_num, MAX(amount_paid_denom) as paid_denom')
            ->first();

        $totalAmount = Money::fromFraction($total->total_num ?? 0, $total->total_denom ?? 100);
        $paidAmount = Money::fromFraction($paid->paid_num ?? 0, $paid->paid_denom ?? 100);

        return $totalAmount->subtract($paidAmount);
    }

    public function getPayableBalance(): Money
    {
        $total = $this->bills()
            ->whereIn('status', ['sent', 'viewed', 'partial', 'overdue'])
            ->selectRaw('SUM(total_num) as total_num, MAX(total_denom) as total_denom')
            ->first();

        $paid = $this->bills()
            ->whereIn('status', ['sent', 'viewed', 'partial', 'overdue'])
            ->selectRaw('SUM(amount_paid_num) as paid_num, MAX(amount_paid_denom) as paid_denom')
            ->first();

        $totalAmount = Money::fromFraction($total->total_num ?? 0, $total->total_denom ?? 100);
        $paidAmount = Money::fromFraction($paid->paid_num ?? 0, $paid->paid_denom ?? 100);

        return $totalAmount->subtract($paidAmount);
    }

    public function getBalance(): Money
    {
        if ($this->type === 'customer') {
            return $this->getReceivableBalance();
        }

        return $this->getPayableBalance();
    }
}
