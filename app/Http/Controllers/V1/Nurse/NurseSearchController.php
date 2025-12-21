<?php

namespace App\Http\Controllers\V1\Nurse;

use App\Http\Controllers\Controller;
use App\Models\NurseProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NurseSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'integer', 'min:1', 'max:200'],
            'city' => ['nullable', 'string', 'max:80'],
            'verified' => ['nullable', 'boolean'],
        ]);

        $lat = (float) $validated['lat'];
        $lng = (float) $validated['lng'];
        $radiusKm = (int) ($validated['radius_km'] ?? 10);
        $city = $validated['city'] ?? null;

        // Default: show verified nurses unless explicitly set
        $verified = array_key_exists('verified', $validated) ? $validated['verified'] : true;

        $distanceSql = <<<SQL
(6371 * acos(
    cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?))
    + sin(radians(?)) * sin(radians(lat))
))
SQL;

        $query = NurseProfile::query()
            ->whereNotNull('lat')
            ->whereNotNull('lng');

        if ($city) {
            $query->where('city', $city);
        }

        if (!is_null($verified)) {
            $query->where('verified', (bool) $verified);
        }

        // join aggregated ratings
        $query->leftJoinSub(
            \DB::table('reviews')
                ->selectRaw('nurse_user_id, AVG(rating) as rating_avg, COUNT(*) as reviews_count')
                ->groupBy('nurse_user_id'),
            'r',
            'r.nurse_user_id',
            '=',
            'nurse_profiles.user_id'
        );

        $query->select([
            'nurse_profiles.*',
        ])
            ->selectRaw("$distanceSql as distance_km", [$lat, $lng, $lat])
            ->selectRaw('COALESCE(r.rating_avg, 0) as rating_avg')
            ->selectRaw('COALESCE(r.reviews_count, 0) as reviews_count')
            ->whereRaw("$distanceSql <= ?", [$lat, $lng, $lat, $radiusKm])
            ->orderBy('distance_km', 'asc')
            ->limit(50);

        $results = $query->get();

        return response()->json([
            'data' => $results->map(fn ($n) => [
                'user_id' => $n->user_id,
                'city' => $n->city,
                'address' => $n->address,
                'lat' => $n->lat,
                'lng' => $n->lng,
                'coverage_km' => $n->coverage_km,
                'price_min' => $n->price_min,
                'price_max' => $n->price_max,
                'verified' => (bool) $n->verified,
                'distance_km' => round((float) $n->distance_km, 2),
                'rating_avg' => round((float) $n->rating_avg, 2),
                'reviews_count' => (int) $n->reviews_count,
            ]),
        ]);
    }
}
