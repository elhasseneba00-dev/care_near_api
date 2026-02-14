<?php

namespace App\Http\Controllers\V1\Nurse;

use App\Http\Controllers\Controller;
use App\Models\NurseProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NurseSearchController extends Controller
{
    /**
     * @OA\Get(
     *   path="/nurses/search",
     *   tags={"Nurse Search"},
     *   summary="Search nearby nurses by geolocation",
     *   description="Returns up to 50 verified nurses within the given radius, sorted by distance. Includes average rating and review count.",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="lat", in="query", required=true, description="Latitude of search center", @OA\Schema(type="number", format="double", example=18.102)),
     *   @OA\Parameter(name="lng", in="query", required=true, description="Longitude of search center", @OA\Schema(type="number", format="double", example=-15.958)),
     *   @OA\Parameter(name="radius_km", in="query", required=false, description="Search radius in km (default 10, max 200)", @OA\Schema(type="integer", minimum=1, maximum=200, default=10)),
     *   @OA\Parameter(name="city", in="query", required=false, description="Filter by city name", @OA\Schema(type="string", maxLength=80)),
     *   @OA\Parameter(name="verified", in="query", required=false, description="Filter by verification status (default true)", @OA\Schema(type="boolean", default=true)),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       @OA\Property(property="data", type="array",
     *         @OA\Items(type="object",
     *           @OA\Property(property="user_id", type="integer", example=5),
     *           @OA\Property(property="city", type="string", example="Nouakchott"),
     *           @OA\Property(property="address", type="string", example="Tevragh Zeina"),
     *           @OA\Property(property="lat", type="number", format="double", example=18.102),
     *           @OA\Property(property="lng", type="number", format="double", example=-15.958),
     *           @OA\Property(property="coverage_km", type="integer", example=10),
     *           @OA\Property(property="price_min", type="integer", example=500),
     *           @OA\Property(property="price_max", type="integer", example=2000),
     *           @OA\Property(property="verified", type="boolean", example=true),
     *           @OA\Property(property="distance_km", type="number", format="double", example=2.45),
     *           @OA\Property(property="rating_avg", type="number", format="double", example=4.5),
     *           @OA\Property(property="reviews_count", type="integer", example=12)
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=422, description="Validation error (lat/lng required)")
     * )
     */
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
