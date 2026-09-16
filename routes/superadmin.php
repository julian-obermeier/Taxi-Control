<?php

use App\Http\Controllers\Superadmin\DashboardController;
use App\Http\Controllers\Superadmin\FeatureController;
use App\Http\Controllers\Superadmin\PackageController;
use App\Http\Controllers\Superadmin\SystemSettingController;
use App\Http\Controllers\Superadmin\TenantController;
use App\Http\Controllers\Superadmin\UpdateCenterController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');
Route::resource('tenants', TenantController::class)->except(['show']);
Route::resource('packages', PackageController::class)->except(['show']);
Route::resource('features', FeatureController::class)->except(['show']);
Route::get('/settings', [SystemSettingController::class, 'index'])->name('settings.index');
Route::put('/settings', [SystemSettingController::class, 'update'])->name('settings.update');
Route::get('/update', [UpdateCenterController::class, 'index'])->name('update.index');
Route::post('/update', [UpdateCenterController::class, 'apply'])->name('update.apply');
