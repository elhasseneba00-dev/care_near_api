<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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
        'medical_notes',
        'medical_files',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'lat'        => 'double',
            'lng'        => 'double',
            'medical_files' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Retourne les URLs signées temporaires (15 min) pour chaque document médical.
     */
    public function getMedicalDocumentUrlsAttribute(): array
    {
        if (empty($this->medical_files)) {
            return [];
        }

        return collect($this->medical_files)->map(function (string $path) {
            return [
                'path'      => basename($path),
                'url'       => Storage::disk('local')->temporaryUrl($path, now()->addMinutes(15)),
                'full_path' => $path,
            ];
        })->values()->all();
    }
}
