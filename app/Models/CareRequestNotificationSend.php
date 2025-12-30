<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareRequestNotificationSend extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'care_request_id',
        'nurse_user_id',
        'event',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
