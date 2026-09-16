<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    public function __construct(private readonly PermissionService $permissions)
    {
    }

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        abort_unless($user && $this->permissions->allows($user, $permission), 403, 'Für diese Aktion fehlt die erforderliche Berechtigung.');

        return $next($request);
    }
}
