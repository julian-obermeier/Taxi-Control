<?php

use App\Http\Controllers\Tenant\ApiClientController;
use App\Http\Controllers\Tenant\AuditController;
use App\Http\Controllers\Tenant\BrandingController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\DispatchController;
use App\Http\Controllers\Tenant\DriverController;
use App\Http\Controllers\Tenant\OnboardingController;
use App\Http\Controllers\Tenant\PrivacyController;
use App\Http\Controllers\Tenant\RoleController;
use App\Http\Controllers\Tenant\SettingsController;
use App\Http\Controllers\Tenant\TripController;
use App\Http\Controllers\Tenant\UserController;
use App\Http\Controllers\Tenant\VehicleController;
use App\Http\Controllers\Tenant\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', DashboardController::class)->middleware('permission:dashboard.view')->name('dashboard');

Route::get('/disposition', DispatchController::class)->middleware('permission:dispatch.view')->name('dispatch.index');
Route::get('/fahrten', [TripController::class, 'index'])->middleware('permission:dispatch.view')->name('trips.index');
Route::get('/fahrten/neu', [TripController::class, 'create'])->middleware('permission:dispatch.create')->name('trips.create');
Route::post('/fahrten', [TripController::class, 'store'])->middleware('permission:dispatch.create')->name('trips.store');
Route::get('/fahrten/{tripId}', [TripController::class, 'show'])->middleware('permission:dispatch.view')->whereNumber('tripId')->name('trips.show');
Route::get('/fahrten/{tripId}/bearbeiten', [TripController::class, 'edit'])->middleware('permission:dispatch.update')->whereNumber('tripId')->name('trips.edit');
Route::put('/fahrten/{tripId}', [TripController::class, 'update'])->middleware('permission:dispatch.update')->whereNumber('tripId')->name('trips.update');
Route::put('/fahrten/{tripId}/zuweisung', [TripController::class, 'assign'])->middleware('permission:dispatch.assign')->whereNumber('tripId')->name('trips.assign');
Route::put('/fahrten/{tripId}/status', [TripController::class, 'status'])->middleware('permission:dispatch.status')->whereNumber('tripId')->name('trips.status');
Route::delete('/fahrten/{tripId}', [TripController::class, 'destroy'])->middleware('permission:dispatch.delete')->whereNumber('tripId')->name('trips.destroy');

Route::get('/fahrer', [DriverController::class, 'index'])->middleware('permission:drivers.view')->name('drivers.index');
Route::get('/fahrer/neu', [DriverController::class, 'create'])->middleware('permission:drivers.create')->name('drivers.create');
Route::post('/fahrer', [DriverController::class, 'store'])->middleware('permission:drivers.create')->name('drivers.store');
Route::get('/fahrer/{driverId}/bearbeiten', [DriverController::class, 'edit'])->middleware('permission:drivers.update')->whereNumber('driverId')->name('drivers.edit');
Route::put('/fahrer/{driverId}', [DriverController::class, 'update'])->middleware('permission:drivers.update')->whereNumber('driverId')->name('drivers.update');
Route::delete('/fahrer/{driverId}', [DriverController::class, 'destroy'])->middleware('permission:drivers.delete')->whereNumber('driverId')->name('drivers.destroy');

Route::get('/fahrzeuge', [VehicleController::class, 'index'])->middleware('permission:vehicles.view')->name('vehicles.index');
Route::get('/fahrzeuge/neu', [VehicleController::class, 'create'])->middleware('permission:vehicles.create')->name('vehicles.create');
Route::post('/fahrzeuge', [VehicleController::class, 'store'])->middleware('permission:vehicles.create')->name('vehicles.store');
Route::get('/fahrzeuge/{vehicleId}/bearbeiten', [VehicleController::class, 'edit'])->middleware('permission:vehicles.update')->whereNumber('vehicleId')->name('vehicles.edit');
Route::put('/fahrzeuge/{vehicleId}', [VehicleController::class, 'update'])->middleware('permission:vehicles.update')->whereNumber('vehicleId')->name('vehicles.update');
Route::delete('/fahrzeuge/{vehicleId}', [VehicleController::class, 'destroy'])->middleware('permission:vehicles.delete')->whereNumber('vehicleId')->name('vehicles.destroy');

Route::get('/benutzer', [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
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
Route::get('/einstellungen', [SettingsController::class, 'index'])->middleware('permission:settings.view')->name('settings.index');
Route::put('/einstellungen', [SettingsController::class, 'update'])->middleware('permission:settings.update')->name('settings.update');
Route::get('/branding', [BrandingController::class, 'edit'])->middleware('permission:branding.view')->name('branding.edit');
Route::put('/branding', [BrandingController::class, 'update'])->middleware('permission:branding.update')->name('branding.update');
Route::get('/onboarding', [OnboardingController::class, 'index'])->middleware('permission:onboarding.view')->name('onboarding.index');
Route::post('/onboarding/abschliessen', [OnboardingController::class, 'complete'])->middleware('permission:onboarding.update')->name('onboarding.complete');
Route::post('/onboarding/go-live', [OnboardingController::class, 'goLive'])->middleware('permission:onboarding.update')->name('onboarding.go-live');
Route::get('/api', [ApiClientController::class, 'index'])->middleware('permission:api.view')->name('api.index');
Route::post('/api', [ApiClientController::class, 'store'])->middleware('permission:api.create')->name('api.store');
Route::delete('/api/{client}', [ApiClientController::class, 'destroy'])->middleware('permission:api.delete')->name('api.destroy');
Route::get('/webhooks', [WebhookController::class, 'index'])->middleware('permission:webhooks.view')->name('webhooks.index');
Route::post('/webhooks', [WebhookController::class, 'store'])->middleware('permission:webhooks.create')->name('webhooks.store');
Route::put('/webhooks/{webhook}', [WebhookController::class, 'update'])->middleware('permission:webhooks.update')->name('webhooks.update');
Route::post('/webhooks/{webhook}/test', [WebhookController::class, 'test'])->middleware('permission:webhooks.update')->name('webhooks.test');
Route::delete('/webhooks/{webhook}', [WebhookController::class, 'destroy'])->middleware('permission:webhooks.delete')->name('webhooks.destroy');
Route::get('/datenschutz', [PrivacyController::class, 'index'])->middleware('permission:privacy.view')->name('privacy.index');
Route::post('/datenschutz', [PrivacyController::class, 'store'])->middleware('permission:privacy.update')->name('privacy.store');
Route::put('/datenschutz/{privacyRequest}', [PrivacyController::class, 'update'])->middleware('permission:privacy.update')->name('privacy.update');
Route::get('/audit', [AuditController::class, 'index'])->middleware('permission:audit.view')->name('audit.index');
