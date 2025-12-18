<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientProfile extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'birth_date',
        'gender',
        'city',
        'address',
        'lat',
        'lng',
        'medical_notes'
    ];
}
