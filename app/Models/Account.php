<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
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
        'parent_id',
        'account_type_id',
        'currency_id',
        'code',
        'name',
        'description',
        'is_placeholder',
        'is_hidden',
        'path',
        'level',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_placeholder' => 'boolean',
        'is_hidden' => 'boolean',
        'level' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Account $account) {
            $account->updatePathAndLevel();
        });
    }

    /**
     * The company this account belongs to.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The parent account.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /**
     * Child accounts.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    /**
     * The account type.
     */
    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class);
    }

    /**
     * The currency for this account.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Splits (transactions entries) for this account.
     */
    public function splits(): HasMany
    {
        return $this->hasMany(Split::class);
    }

    /**
     * Get all ancestors (parent, grandparent, etc.).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Account>
     */
    public function getAncestors(): \Illuminate\Database\Eloquent\Collection
    {
        $ancestors = collect();
        $parent = $this->parent;

        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }

        return $ancestors;
    }

    /**
     * Get all descendants (children, grandchildren, etc.).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Account>
     */
    public function getDescendants(): \Illuminate\Database\Eloquent\Collection
    {
        $descendants = new \Illuminate\Database\Eloquent\Collection();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $childDescendants = $child->getDescendants();
            foreach ($childDescendants as $desc) {
                $descendants->push($desc);
            }
        }

        return $descendants;
    }

    /**
     * Calculate the depth of this account in the hierarchy.
     *
     * @param  string|null  $parentId  Optional parent ID (for calculating before save)
     */
    public function calculateDepth(?string $parentId = null): int
    {
        $targetParentId = $parentId ?? $this->parent_id;

        if (! $targetParentId) {
            return 0;
        }

        $parent = static::find($targetParentId);

        if (! $parent) {
            return 0;
        }

        return ($parent->depth ?? 0) + 1;
    }

    /**
     * Update the path and level based on parent.
     */
    public function updatePathAndLevel(): void
    {
        if ($this->parent_id) {
            $parent = $this->parent()->withoutGlobalScopes()->first();
            if ($parent) {
                $this->path = $parent->path ? "{$parent->path}/{$this->id}" : $this->id;
                $this->level = $parent->level + 1;
            }
        } else {
            $this->path = $this->id;
            $this->level = 0;
        }
    }

    /**
     * Check if this account is a leaf (has no children).
     */
    public function isLeaf(): bool
    {
        return $this->children()->count() === 0;
    }

    /**
     * Check if this account can have transactions (not a placeholder).
     */
    public function canHaveTransactions(): bool
    {
        return ! $this->is_placeholder;
    }

    /**
     * Scope to get only visible accounts.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Scope to get only root accounts (no parent).
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get only accounts that can have transactions.
     */
    public function scopeTransactable($query)
    {
        return $query->where('is_placeholder', false);
    }

    /**
     * Scope to filter by account type.
     */
    public function scopeOfType($query, string $typeName)
    {
        return $query->whereHas('accountType', function ($q) use ($typeName) {
            $q->where('name', $typeName);
        });
    }

    /**
     * Get the full path name (including ancestors).
     */
    public function getFullPathNameAttribute(): string
    {
        $names = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($names, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' > ', $names);
    }
}
