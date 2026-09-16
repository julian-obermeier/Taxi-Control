<?php

namespace App\Http\Middleware;

use App\Services\FeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    public function __construct(private readonly FeatureService $features)
    {
    }

    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        abort_unless($this->features->enabled($featureKey), 403, 'Dieses Modul ist für den aktuellen Mandanten nicht freigeschaltet.');
        return $next($request);
    }
}
