<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CareRequest extends Model
{
    protected $fillable = [
        'patient_user_id',
        'nurse_user_id',
        'care_type',
        'description',
        'scheduled_at',
        'address',
        'city',
        'lat',
        'lng',
        'status',
        // --- Colonnes manquantes ajoutées ---
        'visibility',
        'target_nurse_user_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_user_id');
    }

    public function nurse(): BelongsTo{
        return $this->belongsTo(User::class, 'nurse_user_id');
    }

    public function targetNurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_nurse_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'care_request_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class, 'care_request_id');
    }

    public function ignores(): HasMany
    {
        return $this->hasMany(CareRequestIgnore::class, 'care_request_id');
    }
}
