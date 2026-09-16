<?php

use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\RoleController;
use App\Http\Controllers\Tenant\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', DashboardController::class)->middleware('permission:dashboard.view')->name('dashboard');

Route::middleware('permission:users.view')->group(function (): void {
    Route::get('/benutzer', [UserController::class, 'index'])->name('users.index');
});
Route::get('/benutzer/neu', [UserController::class, 'create'])->middleware('permission:users.create')->name('users.create');
Route::post('/benutzer', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
Route::get('/benutzer/{user}/bearbeiten', [UserController::class, 'edit'])->middleware('permission:users.update')->name('users.edit');
Route::put('/benutzer/{user}', [UserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
Route::delete('/benutzer/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');

Route::get('/rollen', [RoleController::class, 'index'])->middleware('permission:roles.view')->name('roles.index');
Route::get('/rollen/neu', [RoleController::class, 'create'])->middleware('permission:roles.create')->name('roles.create');
Route::post('/rollen', [RoleController::class, 'store'])->middleware('permission:roles.create')->name('roles.store');
Route::get('/rollen/{role}/bearbeiten', [RoleController::class, 'edit'])->middleware('permission:roles.update')->name('roles.edit');
Route::put('/rollen/{role}', [RoleController::class, 'update'])->middleware('permission:roles.update')->name('roles.update');
Route::delete('/rollen/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete')->name('roles.destroy');
