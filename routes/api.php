<?php

use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\V1\Admin\AdminAuditLogController;
use App\Http\Controllers\V1\Admin\AdminCareRequestController;
use App\Http\Controllers\V1\Admin\AdminCareRequestMessageController;
use App\Http\Controllers\V1\Admin\AdminNurseVerificationController;
use App\Http\Controllers\V1\Admin\AdminStatsController;
use App\Http\Controllers\V1\Admin\AdminUserController;
use App\Http\Controllers\V1\Care\CareRequestController;
use App\Http\Controllers\V1\Chat\MessageController;
use App\Http\Controllers\V1\Favorite\FavoriteController;
use App\Http\Controllers\V1\Notification\NotificationController;
use App\Http\Controllers\V1\Nurse\NurseDocumentController;
use App\Http\Controllers\V1\Nurse\NurseProfileController;
use App\Http\Controllers\V1\Nurse\NurseSearchController;
use App\Http\Controllers\V1\Patient\PatientDocumentController;
use App\Http\Controllers\V1\Patient\PatientProfileController;
use App\Http\Controllers\V1\Review\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::prefix('v1')->group(function () {
    require __DIR__.'/auth.php';

    // Public routes (no auth needed)
    Route::get('/nurses/{nurseUserId}/reviews', [ReviewController::class, 'indexByNurse']);
    Route::get('/nurses/{nurseUserId}/rating', [ReviewController::class, 'ratingByNurse']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [MeController::class, 'me']);

        // Patient profile + documents
        Route::get('/patient/profile', [PatientProfileController::class, 'show']);
        Route::post('/patient/profile', [PatientProfileController::class, 'upsert']);
        Route::get('/patient/profile/documents/{index}/download', [PatientDocumentController::class, 'downloadDocument']);
        Route::delete('/patient/profile/documents/{index}', [PatientProfileController::class, 'deleteDocument']);

        // Nurse profile + diploma
        Route::get('/nurse/profile', [NurseProfileController::class, 'show']);
        Route::post('/nurse/profile', [NurseProfileController::class, 'upsert']);
        Route::get('/nurse/profile/diploma', [NurseDocumentController::class, 'downloadDiploma']);

        // Nurse search
        Route::get('/nurses/search', [NurseSearchController::class, 'search']);

        // Care requests
        Route::post('/care-requests', [CareRequestController::class, 'store'])
            ->middleware('throttle:10,60');
        Route::get('/care-requests', [CareRequestController::class, 'index']);
        Route::get('/care-requests/{careRequest}', [CareRequestController::class, 'show']);

        Route::post('/care-requests/{careRequest}/accept', [CareRequestController::class, 'accept']);
        Route::post('/care-requests/{careRequest}/reject', [CareRequestController::class, 'reject']);
        Route::post('/care-requests/{careRequest}/cancel', [CareRequestController::class, 'cancel']);
        Route::post('/care-requests/{careRequest}/complete', [CareRequestController::class, 'complete']);
        Route::post('/care-requests/{careRequest}/rebook', [CareRequestController::class, 'rebook']);
        Route::post('/care-requests/{careRequest}/ignore', [CareRequestController::class, 'ignore']);

        // Chat
        Route::get('/care-requests/{careRequest}/messages', [MessageController::class, 'index']);
        Route::post('/care-requests/{careRequest}/messages', [MessageController::class, 'store'])
            ->middleware('throttle:20,1');

        // Reviews
        Route::post('/care-requests/{careRequest}/review', [ReviewController::class, 'store']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

        // Favorites
        Route::get('/favorites', [FavoriteController::class, 'index']);
        Route::post('/favorites/{nurseUserId}', [FavoriteController::class, 'store']);
        Route::delete('/favorites/{nurseUserId}', [FavoriteController::class, 'destroy']);

        // Admin
        Route::prefix('admin')->middleware('role:ADMIN')->group(function () {
            Route::get('/nurses', [AdminNurseVerificationController::class, 'index']);
            Route::post('/nurses/{nurseUserId}/verify', [AdminNurseVerificationController::class, 'verify']);
            Route::get('/stats', [AdminStatsController::class, 'show']);
            Route::get('/care-requests', [AdminCareRequestController::class, 'index']);
            Route::get('/care-requests/{careRequest}/messages', [AdminCareRequestMessageController::class, 'index']);
            Route::get('/users', [AdminUserController::class, 'index']);
            Route::post('/users/{userId}/suspend', [AdminUserController::class, 'suspend']);
            Route::post('/users/{userId}/unsuspend', [AdminUserController::class, 'unsuspend']);
            Route::get('/audit-logs', [AdminAuditLogController::class, 'index']);
        });
    });
});
