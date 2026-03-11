<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatch extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'company_id',
        'account_id',
        'file_name',
        'file_path',
        'type',
        'status',
        'total_rows',
        'matched_count',
        'created_count',
        'error_count',
        'errors',
        'mapping',
        'imported_by',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'matched_count' => 'integer',
        'created_count' => 'integer',
        'error_count' => 'integer',
        'errors' => 'array',
        'mapping' => 'array',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Methods
    public function markProcessing(): void
    {
        $this->status = 'processing';
        $this->save();
    }

    public function markCompleted(): void
    {
        $this->status = 'completed';
        $this->save();
    }

    public function markFailed(array $errors = []): void
    {
        $this->status = 'failed';
        $this->errors = array_merge($this->errors ?? [], $errors);
        $this->error_count = count($this->errors);
        $this->save();
    }

    public function addError(string $error, ?int $row = null): void
    {
        $errors = $this->errors ?? [];
        $errors[] = [
            'message' => $error,
            'row' => $row,
            'timestamp' => now()->toIso8601String(),
        ];
        $this->errors = $errors;
        $this->error_count = count($errors);
        $this->save();
    }

    public function incrementMatched(): void
    {
        $this->increment('matched_count');
    }

    public function incrementCreated(): void
    {
        $this->increment('created_count');
    }

    public function getProgressPercentAttribute(): int
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return (int) round(($this->matched_count + $this->created_count) / $this->total_rows * 100);
    }

    public function getIsCompleteAttribute(): bool
    {
        return in_array($this->status, ['completed', 'failed']);
    }
}
