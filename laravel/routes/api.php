<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\EstimateController;
use App\Http\Controllers\Api\RatesController;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'healthy',
            'backend' => 'Laravel ' . app()->version(),
            'groq_configured' => !empty(config('construction.groq.api_key')),
            'groq_model' => config('construction.groq.model')
        ]);
    });

    Route::post('/chat', [ChatController::class, 'chat']);

    Route::prefix('estimate')->group(function () {
        Route::post('/by-rooms', [EstimateController::class, 'byRooms']);
        Route::post('/by-room', [EstimateController::class, 'byRooms']);
        Route::post('/residential', [EstimateController::class, 'residential']);
        Route::post('/rebar-schedule', [EstimateController::class, 'rebarSchedule']);
        Route::post('/bridge-pile', [EstimateController::class, 'bridgePile']);
        Route::post('/swimming-pool', [EstimateController::class, 'swimmingPool']);
    });

    Route::get('/rates', [RatesController::class, 'index']);
});
