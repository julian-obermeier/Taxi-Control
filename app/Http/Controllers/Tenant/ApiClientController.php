<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\Tenant;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ApiClientController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function index(Tenant $tenant): View
    {
        return view('tenant.api.index', ['tenant' => $tenant, 'clients' => ApiClient::query()->latest()->get()]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => ['in:*,read,write,dispatch,crm,billing,fleet'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);
        $publicKey = 'tc_'.Str::lower(Str::random(24));
        $secret = Str::random(48);
        $client = ApiClient::query()->create([
            ...$data,
            'public_key' => $publicKey,
            'secret_hash' => hash('sha256', $secret),
            'is_active' => true,
        ]);
        $this->audit->log('tenant.api_client.created', $client, [], ['name' => $client->name, 'public_key' => $publicKey], [], $tenant->id);
        return back()->with('status', 'API-Schlüssel wurde erstellt. Kopiere ihn jetzt – das Secret wird nicht erneut angezeigt.')->with('api_token', $publicKey.'.'.$secret);
    }

    public function destroy(Tenant $tenant, ApiClient $client): RedirectResponse
    {
        abort_unless($client->tenant_id === $tenant->id, 404);
        $client->delete();
        $this->audit->log('tenant.api_client.deleted', null, ['id' => $client->id, 'name' => $client->name], [], [], $tenant->id);
        return back()->with('status', 'API-Schlüssel wurde widerrufen.');
    }
}
