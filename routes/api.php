<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', fn () => response()->json([
        'service' => 'Taxi-Control API',
        'version' => 'v1',
        'status' => 'ok',
    ]));
});
