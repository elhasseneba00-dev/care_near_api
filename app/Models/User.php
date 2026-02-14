<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * @var \Illuminate\Support\HigherOrderCollectionProxy|mixed
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'phone',
        'role',
        'email',
        'password',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ── Relations ────────────────────────────────────────

    public function nurseProfile(): HasOne
    {
        return $this->hasOne(NurseProfile::class, 'user_id');
    }

    public function patientProfile(): HasOne
    {
        return $this->hasOne(PatientProfile::class, 'user_id');
    }

    /**
     * Care requests created by this user (as patient).
     */
    public function careRequestsAsPatient(): HasMany
    {
        return $this->hasMany(CareRequest::class, 'patient_user_id');
    }

    /**
     * Care requests assigned to this user (as nurse).
     */
    public function careRequestsAsNurse(): HasMany
    {
        return $this->hasMany(CareRequest::class, 'nurse_user_id');
    }

    public function reviewsGiven(): HasMany
    {
        return $this->hasMany(Review::class, 'patient_user_id');
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'nurse_user_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class, 'patient_user_id');
    }

    // ── Helpers ──────────────────────────────────────────

    public function isPatient(): bool
    {
        return $this->role === 'PATIENT';
    }

    public function isNurse(): bool
    {
        return $this->role === 'NURSE';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }
}
