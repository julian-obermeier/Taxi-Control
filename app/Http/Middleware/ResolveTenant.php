<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $routeTenant = $request->route('tenant');
        $slug = $routeTenant instanceof Tenant ? $routeTenant->slug : (string) $routeTenant;

        $tenant = Tenant::query()
            ->where('slug', $slug)
            ->whereIn('status', ['trial', 'active'])
            ->firstOrFail();

        $this->context->set($tenant);
        $request->route()->setParameter('tenant', $tenant);

        try {
            return $next($request);
        } finally {
            $this->context->clear();
        }
    }
}
