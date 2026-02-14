<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'care_request_id',
        'patient_user_id',
        'nurse_user_id',
        'rating',
        'comment',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'rating'     => 'integer',
        ];
    }

    public function careRequest(): BelongsTo
    {
        return $this->belongsTo(CareRequest::class, 'care_request_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_user_id');
    }

    public function nurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nurse_user_id');
    }
}
