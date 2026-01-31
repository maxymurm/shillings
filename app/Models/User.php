<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use HasUuids;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'current_company_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Determine if the user can access Filament.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true; // All users can access admin panel for now
    }

    /**
     * The current company the user is working in.
     */
    public function currentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'current_company_id');
    }

    /**
     * All companies the user belongs to.
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Transactions created by this user.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    /**
     * Switch to a different company.
     */
    public function switchCompany(Company $company): self
    {
        // Verify user has access to this company
        if (! $this->companies->contains($company)) {
            throw new \RuntimeException('User does not have access to this company');
        }

        $this->current_company_id = $company->id;
        $this->save();

        return $this;
    }

    /**
     * Get the user's role in a specific company.
     */
    public function getRoleInCompany(Company $company): ?string
    {
        $pivot = $this->companies()->where('company_id', $company->id)->first();

        return $pivot?->pivot?->role;
    }

    /**
     * Check if user is owner of a company.
     */
    public function isOwnerOf(Company $company): bool
    {
        return $this->getRoleInCompany($company) === 'owner';
    }

    /**
     * Check if user is admin (or owner) of a company.
     */
    public function isAdminOf(Company $company): bool
    {
        return in_array($this->getRoleInCompany($company), ['owner', 'admin']);
    }
}
