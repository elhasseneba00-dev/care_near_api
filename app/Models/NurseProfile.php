<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NurseProfile extends Model
{
    protected $table = 'nurse_profiles';
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'diploma',
        'experience_years',
        'bio',
        'city',
        'address',
        'lat',
        'lng',
        'coverage_km',
        'price_min',
        'price_max',
        'verified',
    ];
}
