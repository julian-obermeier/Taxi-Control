<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($user->two_factor_enabled_at && $request->session()->get('2fa_passed') !== true) {
            return redirect()->route('taxi-control.2fa.challenge');
        }

        return $next($request);
    }
}
