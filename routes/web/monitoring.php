<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use App\Services\TamaraService;

/*
|--------------------------------------------------------------------------
| Monitoring Routes
|--------------------------------------------------------------------------
|
| These routes are for monitoring payment gateway status and circuit breaker
|
*/

Route::get('/monitor/payment-status', function() {
    $data = [
        'tamara' => [
            'service_available' => TamaraService::isServiceAvailable(),
            'failure_count' => Cache::get('tamara_failure_count', 0),
            'last_failure' => Cache::get('tamara_last_failure'),
            'circuit_breaker_status' => TamaraService::isServiceAvailable() ? 'OPEN' : 'CLOSED'
        ],
        'timestamp' => now()->toDateTimeString()
    ];
    
    return response()->json($data, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
})->name('monitor.payment-status');

Route::get('/monitor/reset-circuit-breaker', function() {
    Cache::forget('tamara_failure_count');
    Cache::forget('tamara_last_failure');
    
    return response()->json([
        'message' => 'Circuit breaker reset successfully',
        'status' => 'success',
        'timestamp' => now()->toDateTimeString()
    ], 200, [], JSON_PRETTY_PRINT);
})->name('monitor.reset-circuit-breaker');
