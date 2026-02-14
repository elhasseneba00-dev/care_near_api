<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class NurseProfile extends Model
{
    protected $table = 'nurse_profiles';
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'diploma',
        'diploma_path',
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

    protected function casts(): array
    {
        return [
            'verified'         => 'boolean',
            'experience_years' => 'integer',
            'coverage_km'      => 'integer',
            'price_min'        => 'integer',
            'price_max'        => 'integer',
            'lat'              => 'double',
            'lng'              => 'double',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Génère une URL signée temporaire (15 min) pour le diplôme.
     * Retourne null si pas de fichier.
     */
    public function getDiplomaUrlAttribute(): ?string
    {
        if (!$this->diploma_path) {
            return null;
        }

        // Disk local privé → URL signée temporaire
        return Storage::disk('local')->temporaryUrl(
            $this->diploma_path,
            now()->addMinutes(15)
        );
    }
}
