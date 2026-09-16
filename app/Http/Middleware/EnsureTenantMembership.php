<?php

namespace App\Http\Middleware;

use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantMembership
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($user->is_superadmin) {
            return $next($request);
        }

        $tenantId = $this->context->requireId();

        $isMember = $user->tenants()
            ->where('tenants.id', $tenantId)
            ->wherePivot('status', 'active')
            ->exists();

        abort_unless($isMember, 403, 'Kein Zugriff auf diesen Mandanten.');

        return $next($request);
    }
}
