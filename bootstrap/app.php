<?php

use App\Http\Middleware\AuthenticateApiClient;
use App\Http\Middleware\EnsureFeatureEnabled;
use App\Http\Middleware\EnsureSuperadmin;
use App\Http\Middleware\EnsureTenantMembership;
use App\Http\Middleware\EnsureTwoFactorVerified;
use App\Http\Middleware\RequirePermission;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant.resolve' => ResolveTenant::class,
            'tenant.member' => EnsureTenantMembership::class,
            'superadmin' => EnsureSuperadmin::class,
            '2fa' => EnsureTwoFactorVerified::class,
            'permission' => RequirePermission::class,
            'feature' => EnsureFeatureEnabled::class,
            'api.client' => AuthenticateApiClient::class,
        ]);

        // Taxi-Control lives below /taxi-control; Laravel's default guest
        // redirect expects a route named "login", while our scoped route is
        // "taxi-control.login". Keep the redirect explicit so protected
        // tenant/superadmin routes never fail with Route [login] not defined.
        $middleware->redirectGuestsTo(fn (Request $request): string => route('taxi-control.login'));

        $middleware->validateCsrfTokens(except: ['api/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Fail-closed: Fachliche Fehler werden über die jeweiligen Handler/HTTP-Statuscodes transportiert.
    })->create();
