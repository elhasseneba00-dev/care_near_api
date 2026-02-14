<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// Download OpenAPI/Swagger schema
Route::get('/api/documentation/download', function () {
    $filePath = storage_path('api-docs/api-docs.json');

    if (!file_exists($filePath)) {
        abort(404, 'API documentation not found. Run: php artisan l5-swagger:generate');
    }

    return response()->download($filePath, 'openapi-schema.json', [
        'Content-Type' => 'application/json',
    ]);
})->middleware(\App\Http\Middleware\DevOnly::class)->name('swagger.download');

