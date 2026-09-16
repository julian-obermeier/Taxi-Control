<?php

use App\Http\Controllers\Superadmin\DashboardController;
use App\Http\Controllers\Superadmin\TenantController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');
Route::resource('tenants', TenantController::class)->except(['show']);
