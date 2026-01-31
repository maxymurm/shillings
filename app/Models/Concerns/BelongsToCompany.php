<?php

namespace App\Models\Concerns;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait for models that belong to a company (multi-tenancy).
 *
 * @mixin Model
 */
trait BelongsToCompany
{
    /**
     * Boot the trait.
     */
    public static function bootBelongsToCompany(): void
    {
        // Auto-set company_id when creating
        static::creating(function (Model $model) {
            if (empty($model->company_id)) {
                $model->company_id = static::getCurrentCompanyId();
            }
        });

        // Global scope to filter by current company
        static::addGlobalScope('company', function (Builder $builder) {
            $companyId = static::getCurrentCompanyId();
            if ($companyId) {
                $builder->where($builder->getModel()->getTable() . '.company_id', $companyId);
            }
        });
    }

    /**
     * Get the current company ID from authenticated user or session.
     */
    protected static function getCurrentCompanyId(): ?string
    {
        // Try to get from authenticated user
        if (auth()->check() && auth()->user()->current_company_id) {
            return auth()->user()->current_company_id;
        }

        // Try to get from session
        if (session()->has('current_company_id')) {
            return session()->get('current_company_id');
        }

        return null;
    }

    /**
     * The company this model belongs to.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope to a specific company, ignoring global scope.
     */
    public function scopeForCompany(Builder $query, string $companyId): Builder
    {
        return $query->withoutGlobalScope('company')
            ->where($this->getTable() . '.company_id', $companyId);
    }

    /**
     * Scope to include all companies (disable global scope).
     */
    public function scopeAllCompanies(Builder $query): Builder
    {
        return $query->withoutGlobalScope('company');
    }
}
