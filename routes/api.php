<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', fn () => response()->json(['service' => 'Taxi-Control API', 'version' => 'v1', 'status' => 'ok']));

    Route::middleware(['api.client:read', 'throttle:60,1'])->group(function (): void {
        Route::get('/me', function (Request $request) {
            $client = $request->attributes->get('api_client');
            $tenant = $request->attributes->get('tenant');
            return response()->json([
                'tenant' => ['id' => $tenant->id, 'slug' => $tenant->slug, 'name' => $tenant->name],
                'client' => ['id' => $client->id, 'name' => $client->name, 'scopes' => $client->scopes],
            ]);
        });
    });
});
