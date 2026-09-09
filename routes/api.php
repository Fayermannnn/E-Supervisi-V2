<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\CycleController;
use App\Http\Controllers\Api\V1\ObservationController;
use App\Http\Controllers\Api\V1\SyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', fn (Request $request) => $request->user())->middleware('auth:sanctum');

Route::prefix('v1')->group(function (): void {
    // Token perangkat (ADR-007)
    Route::post('/auth/token', [AuthTokenController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('api.v1.auth.token');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::delete('/auth/token/{tokenId}', [AuthTokenController::class, 'destroy']);

        // Siklus (Spec §8) — @provisional untuk endpoint tahap pasca-observasi
        Route::get('/cycles', [CycleController::class, 'index']);
        Route::post('/cycles', [CycleController::class, 'store']);
        Route::get('/cycles/{cycle}', [CycleController::class, 'show']);
        Route::patch('/cycles/{cycle}/schedule', [CycleController::class, 'schedule']);
        Route::post('/cycles/{cycle}/cancel', [CycleController::class, 'cancel']);
        Route::post('/cycles/{cycle}/reflections', [CycleController::class, 'reflections']);

        // Observasi (M2)
        Route::post('/cycles/{cycle}/observations', [ObservationController::class, 'store']);
        Route::patch('/observations/{observation}', [ObservationController::class, 'update']);
        Route::post('/observations/{observation}/finalize', [ObservationController::class, 'finalize']);
        Route::post('/observations/{observation}/media', [ObservationController::class, 'media']);

        // Sinkronisasi PWA luring (ADR-006)
        Route::middleware('throttle:60,1')->group(function (): void {
            Route::get('/sync/bootstrap', [SyncController::class, 'bootstrap']);
            Route::post('/sync/observations', [SyncController::class, 'observations'])
                ->middleware('ability:observation:sync');
            Route::get('/sync/status', [SyncController::class, 'status']);
        });
    });
});
