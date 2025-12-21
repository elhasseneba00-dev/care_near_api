<?php

namespace App\Jobs;

use App\Models\CareRequest;
use App\Models\NurseProfile;
use App\Models\User;
use App\Notifications\CareRequestNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyNearByNursesOfNewCareRequest implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $careRequestId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $care = CareRequest::query()->find($this->careRequestId);
        if (!$care) return;

        // Only for open pending requests
        if ($care->status !== 'PENDING' || $care->nurse_user_id !== null) return;

        // Don’t spam if no city (fallback) and no lat/lng
        $hasLatLng = $care->lat !== null && $care->lng !== null;
        $hasCity = !empty($care->city);

        if (!$hasLatLng && !$hasCity) return;

        $nurseProfiles = NurseProfile::query()
            ->whereNotNull('user_id')
            ->when($hasCity, fn ($q) => $q->orWhere('city', $care->city))
            ->get();

        // Haversine in PHP (MVP) to keep it simple and avoid heavy SQL per nurse.
        // For scale, we’ll move to SQL + bounding box later.
        foreach ($nurseProfiles as $profile) {
            $nurseUser = User::query()->find($profile->user_id);
            if (!$nurseUser || $nurseUser->role !== 'NURSE' || $nurseUser->status !== 'ACTIVE') {
                continue;
            }

            // Prefer radius match if both sides have lat/lng
            $withinRadius = false;

            if ($hasLatLng && $profile->lat !== null && $profile->lng !== null) {
                $distanceKm = $this->haversineKm((float)$care->lat, (float)$care->lng, (float)$profile->lat, (float)$profile->lng);
                $coverageKm = (int) ($profile->coverage_km ?? 10);
                $withinRadius = $distanceKm <= $coverageKm;
            }

            $matchesCity = $hasCity && !empty($profile->city) && $profile->city === $care->city;

            if (!$withinRadius && !$matchesCity) {
                continue;
            }

            $nurseUser->notify(new CareRequestNotification(
                event: 'CREATED',
                careRequest: $care,
                message: "Nouvelle demande de soins disponible à {$care->city}."
            ));
        }
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
