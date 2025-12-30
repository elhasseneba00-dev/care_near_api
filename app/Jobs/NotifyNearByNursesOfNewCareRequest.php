<?php

namespace App\Jobs;

use App\Models\CareRequest;
use App\Models\CareRequestNotificationSend;
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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $maxNurses = 50;

    public function __construct(public int $careRequestId) {}

    public function handle(): void
    {
        $care = CareRequest::query()->find($this->careRequestId);
        if (!$care) return;

        if ($care->status !== 'PENDING' || $care->nurse_user_id !== null) return;

        $hasLatLng = $care->lat !== null && $care->lng !== null;
        $hasCity = !empty($care->city);

        if (!$hasLatLng && !$hasCity) return;

        $profiles = NurseProfile::query()
            ->where('verified', true)
            ->get();

        $candidates = [];

        foreach ($profiles as $profile) {
            if (!$profile->user_id) continue;

            $nurseUser = User::query()->find($profile->user_id);
            if (!$nurseUser || $nurseUser->role !== 'NURSE' || $nurseUser->status !== 'ACTIVE') {
                continue;
            }

            $distanceKm = null;
            $withinRadius = false;

            if ($hasLatLng && $profile->lat !== null && $profile->lng !== null) {
                $distanceKm = $this->haversineKm((float)$care->lat, (float)$care->lng, (float)$profile->lat, (float)$profile->lng);
                $coverageKm = (int) ($profile->coverage_km ?? 10);
                $withinRadius = $distanceKm <= $coverageKm;
            }

            $matchesCity = $hasCity && !empty($profile->city) && $profile->city === $care->city;

            if (!$withinRadius && !$matchesCity) continue;

            $candidates[] = [
                'user' => $nurseUser,
                'priority' => $withinRadius ? 0 : 1,
                'distance_km' => $distanceKm,
            ];
        }

        if (empty($candidates)) return;

        usort($candidates, function ($a, $b) {
            if ($a['priority'] !== $b['priority']) return $a['priority'] <=> $b['priority'];

            $da = $a['distance_km'];
            $db = $b['distance_km'];

            if ($da === null && $db === null) return 0;
            if ($da === null) return 1;
            if ($db === null) return -1;

            return $da <=> $db;
        });

        $selected = array_slice($candidates, 0, $this->maxNurses);

        foreach ($selected as $c) {
            /** @var User $nurseUser */
            $nurseUser = $c['user'];

            // Idempotency guard
            $send = CareRequestNotificationSend::query()->firstOrCreate(
                [
                    'care_request_id' => $care->id,
                    'nurse_user_id' => $nurseUser->id,
                    'event' => 'CREATED',
                ],
                [
                    'created_at' => now(),
                ]
            );

            // If it already existed, firstOrCreate returns existing row; we must detect "wasRecentlyCreated"
            if (!$send->wasRecentlyCreated) {
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
