<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactPerson extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'contact_persons';

    protected $fillable = [
        'contact_id',
        'name',
        'email',
        'phone',
        'position',
        'department',
        'is_primary',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (ContactPerson $person) {
            // Ensure only one primary per contact
            if ($person->is_primary) {
                static::where('contact_id', $person->contact_id)
                    ->where('id', '!=', $person->id)
                    ->update(['is_primary' => false]);
            }
        });
    }
}
