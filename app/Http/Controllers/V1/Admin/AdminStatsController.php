<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\CareRequest;
use App\Models\NurseProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminStatsController extends Controller
{
    public function show(): JsonResponse
    {
        $usersTotal = User::query()->count();

        $patientsTotal = User::query()->where('role', 'PATIENT')->count();
        $nursesTotal = User::query()->where('role', 'NURSE')->count();
        $adminsTotal = User::query()->where('role', 'ADMIN')->count();

        $verifiedNursesTotal = NurseProfile::query()->where('verified', true)->count();

        $careRequestsTotal = CareRequest::query()->count();

        $careRequestsByStatus = CareRequest::query()
            ->selectRaw('status, COUNT(*)::int as count')
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        $careRequestsByCityTop = CareRequest::query()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->selectRaw('city, COUNT(*)::int as count')
            ->groupBy('city')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $acceptedOrDone = CareRequest::query()
            ->whereIn('status', ['ACCEPTED', 'DONE'])
            ->count();

        $acceptedRate = $careRequestsTotal > 0 ? round(($acceptedOrDone / $careRequestsTotal) * 100, 2) : 0.0;

        return response()->json([
            'data' => [
                'users' => [
                    'total' => $usersTotal,
                    'patients' => $patientsTotal,
                    'nurses' => $nursesTotal,
                    'admins' => $adminsTotal,
                    'verified_nurses' => $verifiedNursesTotal
                ],
                'care_requests' => [
                    'total' => $careRequestsTotal,
                    'acceptance_rate_percent' => $acceptedRate,
                    'by_status' => $careRequestsTotal,
                    'top_city' => $careRequestsByCityTop,
                ]
            ]
        ]);
    }
}
