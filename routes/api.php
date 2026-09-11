<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AccountabilityController;
use App\Http\Controllers\Api\V1\AiGenerationController;
use App\Http\Controllers\Api\V1\AnalysisController;
use App\Http\Controllers\Api\V1\AnnualProgramController;
use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\CycleController;
use App\Http\Controllers\Api\V1\FeedbackController;
use App\Http\Controllers\Api\V1\FollowUpController;
use App\Http\Controllers\Api\V1\ObservationController;
use App\Http\Controllers\Api\V1\ProfessionalDevController;
use App\Http\Controllers\Api\V1\ReportController;
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

        // Tahap pasca-observasi (M3–M6, M18) — @provisional
        Route::get('/cycles/{cycle}/analysis/draft', [AnalysisController::class, 'draft']);
        Route::post('/cycles/{cycle}/analysis', [AnalysisController::class, 'finalize']);
        Route::post('/cycles/{cycle}/feedback', [FeedbackController::class, 'store']);
        Route::patch('/cycles/{cycle}/feedback/ack', [FeedbackController::class, 'acknowledge']);
        Route::post('/cycles/{cycle}/follow-up', [FollowUpController::class, 'store']);
        Route::patch('/follow-up/{item}', [FollowUpController::class, 'updateItem']);
        Route::patch('/follow-up/{item}/evidence', [FollowUpController::class, 'evidence'])
            ->middleware('ability:follow-up:evidence');
        Route::get('/reports/cycle/{cycle}', [ReportController::class, 'cycle']);
        Route::get('/reports/aggregate', [ReportController::class, 'aggregate']);
        Route::post('/cycles/{cycle}/reports/export', [ReportController::class, 'exportCycle']);
        Route::post('/reports/aggregate/export', [ReportController::class, 'exportAggregate']);
        Route::get('/reports/exports/{export}', [ReportController::class, 'exportStatus']);
        Route::post('/ai/generations/{generation}/review', [AiGenerationController::class, 'review']);

        // Pengembangan profesional & akuntabilitas (Fase 4 — M7, M9–M12).
        // @provisional untuk M9–M12.
        Route::get('/programs', [AnnualProgramController::class, 'index']);
        Route::post('/programs', [AnnualProgramController::class, 'store']);
        Route::get('/programs/{program}', [AnnualProgramController::class, 'show']);
        Route::patch('/programs/{program}', [AnnualProgramController::class, 'update']);
        Route::put('/programs/{program}/targets', [AnnualProgramController::class, 'targets']);
        Route::post('/programs/{program}/generate', [AnnualProgramController::class, 'generate']);

        Route::get('/pkb/catalog', [ProfessionalDevController::class, 'catalog']);
        Route::post('/cycles/{cycle}/pkb/recommendations', [ProfessionalDevController::class, 'generateRecommendations']);
        Route::patch('/pkb/recommendations/{recommendation}', [ProfessionalDevController::class, 'respondRecommendation']);
        Route::post('/cycles/{cycle}/best-practice', [ProfessionalDevController::class, 'nominateBestPractice']);
        Route::patch('/best-practices/{bestPractice}/consent', [ProfessionalDevController::class, 'consentBestPractice']);
        Route::patch('/best-practices/{bestPractice}/curate', [ProfessionalDevController::class, 'curateBestPractice']);

        Route::post('/cycles/{cycle}/supervisor-evaluation', [AccountabilityController::class, 'storeEvaluation']);
        Route::get('/accountability/aggregate', [AccountabilityController::class, 'aggregate']);
        Route::post('/calibration/sessions', [AccountabilityController::class, 'createCalibration']);
        Route::post('/calibration/sessions/{session}/participants', [AccountabilityController::class, 'addParticipant']);
        Route::post('/calibration/sessions/{session}/scores', [AccountabilityController::class, 'submitScores']);
        Route::post('/calibration/sessions/{session}/close', [AccountabilityController::class, 'closeCalibration']);

        // Sinkronisasi PWA luring (ADR-006)
        Route::middleware('throttle:60,1')->group(function (): void {
            Route::get('/sync/bootstrap', [SyncController::class, 'bootstrap']);
            Route::post('/sync/observations', [SyncController::class, 'observations'])
                ->middleware('ability:observation:sync');
            Route::post('/sync/follow-up-evidence', [SyncController::class, 'followUpEvidence'])
                ->middleware('ability:follow-up:evidence');
            Route::get('/sync/status', [SyncController::class, 'status']);
        });
    });
});
