<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankConnection extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'account_id',
        'provider',
        'credentials',
        'institution_name',
        'last_sync_at',
        'sync_status',
        'error_message',
        'enabled',
    ];

    protected $casts = [
        'credentials' => 'encrypted',
        'last_sync_at' => 'datetime',
        'enabled' => 'boolean',
    ];

    protected $hidden = [
        'credentials',
    ];

    // Relationships
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    // Scopes
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeNeedsSync($query)
    {
        return $query->enabled()
            ->where(function ($q) {
                $q->whereNull('last_sync_at')
                  ->orWhere('last_sync_at', '<', now()->subDay());
            });
    }

    // Methods
    public function markSyncing(): void
    {
        $this->sync_status = 'syncing';
        $this->error_message = null;
        $this->save();
    }

    public function markSynced(): void
    {
        $this->sync_status = 'completed';
        $this->last_sync_at = now();
        $this->error_message = null;
        $this->save();
    }

    public function markFailed(string $error): void
    {
        $this->sync_status = 'failed';
        $this->error_message = $error;
        $this->save();
    }
}
