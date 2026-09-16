<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstallerController;
use Illuminate\Support\Facades\Route;

// Compatibility for links from the former /taxi-control prefix.
Route::get('/taxi-control/{path?}', function (?string $path = null) {
    return redirect()->to(url('/'.ltrim($path ?? '', '/')), 301);
})->where('path', '.*');

Route::name('taxi-control.')->group(function (): void {
    Route::get('/install', [InstallerController::class, 'show'])->name('install');
    Route::post('/install', [InstallerController::class, 'install'])->name('install.store');

    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [LoginController::class, 'create'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
    });

    Route::middleware('auth')->group(function (): void {
        Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
        Route::get('/2fa/challenge', [TwoFactorController::class, 'challenge'])->name('2fa.challenge');
        Route::post('/2fa/challenge', [TwoFactorController::class, 'verifyChallenge'])->middleware('throttle:10,1')->name('2fa.verify');

        Route::middleware('2fa')->group(function (): void {
            Route::get('/', HomeController::class)->name('home');
            Route::get('/account/2fa', [TwoFactorController::class, 'showSetup'])->name('account.2fa');
            Route::post('/account/2fa/enable', [TwoFactorController::class, 'enable'])->name('account.2fa.enable');
            Route::delete('/account/2fa', [TwoFactorController::class, 'disable'])->name('account.2fa.disable');

            Route::prefix('superadmin')->name('superadmin.')->middleware('superadmin')->group(base_path('routes/superadmin.php'));

            Route::prefix('{tenant}')->name('tenant.')->middleware(['tenant.resolve', 'tenant.member'])->group(base_path('routes/tenant.php'));
        });
    });
});
