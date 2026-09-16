<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiClient
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function handle(Request $request, Closure $next, ?string $requiredScope = null): Response
    {
        $token = (string) $request->bearerToken();
        if (! str_contains($token, '.')) {
            return response()->json(['message' => 'Ungültiger API-Schlüssel.'], 401);
        }

        [$publicKey, $secret] = explode('.', $token, 2);
        $client = ApiClient::query()->withoutGlobalScopes()->where('public_key', $publicKey)->where('is_active', true)->first();

        if (! $client || ($client->expires_at && $client->expires_at->isPast()) || ! hash_equals($client->secret_hash, hash('sha256', $secret))) {
            return response()->json(['message' => 'API-Schlüssel nicht autorisiert.'], 401);
        }

        $scopes = $client->scopes ?? [];
        if ($requiredScope && ! in_array('*', $scopes, true) && ! in_array($requiredScope, $scopes, true)) {
            return response()->json(['message' => 'API-Berechtigung fehlt.'], 403);
        }

        $tenant = Tenant::query()->find($client->tenant_id);
        if (! $tenant || ! in_array($tenant->status, ['trial', 'active'], true)) {
            return response()->json(['message' => 'Mandant ist nicht aktiv.'], 403);
        }

        $this->context->set($tenant);
        $request->attributes->set('api_client', $client);
        $request->attributes->set('tenant', $tenant);
        $client->forceFill(['last_used_at' => now()])->saveQuietly();

        try {
            return $next($request);
        } finally {
            $this->context->clear();
        }
    }
}
