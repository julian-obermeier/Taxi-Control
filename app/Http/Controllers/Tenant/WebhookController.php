<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WebhookController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function index(Tenant $tenant): View
    {
        return view('tenant.webhooks.index', ['tenant' => $tenant, 'webhooks' => Webhook::query()->withCount('deliveries')->latest()->get()]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $this->validated($request);
        $secret = Str::random(48);
        $webhook = Webhook::query()->create([...$data, 'secret_hash' => Crypt::encryptString($secret), 'is_active' => true]);
        $this->audit->log('tenant.webhook.created', $webhook, [], ['name' => $webhook->name, 'endpoint' => $webhook->endpoint], [], $tenant->id);
        return back()->with('status', 'Webhook wurde angelegt. Das Signatur-Secret wird nur jetzt angezeigt.')->with('webhook_secret', $secret);
    }

    public function update(Request $request, Tenant $tenant, Webhook $webhook): RedirectResponse
    {
        abort_unless($webhook->tenant_id === $tenant->id, 404);
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');
        $webhook->update($data);
        return back()->with('status', 'Webhook wurde gespeichert.');
    }

    public function test(Tenant $tenant, Webhook $webhook): RedirectResponse
    {
        abort_unless($webhook->tenant_id === $tenant->id, 404);
        $payload = ['event' => 'system.test', 'tenant_id' => $tenant->id, 'timestamp' => now()->toIso8601String()];
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = hash_hmac('sha256', $json, Crypt::decryptString($webhook->secret_hash));
        $delivery = WebhookDelivery::query()->create(['webhook_id' => $webhook->id, 'event' => 'system.test', 'status' => 'sending', 'payload' => $payload, 'attempt' => 1]);
        try {
            $response = Http::timeout(8)->acceptJson()->withHeaders(['X-Taxi-Control-Signature' => 'sha256='.$signature, 'X-Taxi-Control-Event' => 'system.test'])->withBody($json, 'application/json')->post($webhook->endpoint);
            $delivery->update(['status' => $response->successful() ? 'delivered' : 'failed', 'http_status' => $response->status(), 'response_excerpt' => Str::limit($response->body(), 1000), 'delivered_at' => $response->successful() ? now() : null]);
        } catch (\Throwable $e) {
            $delivery->update(['status' => 'failed', 'response_excerpt' => Str::limit($e->getMessage(), 1000), 'next_retry_at' => now()->addMinutes(5)]);
        }
        return back()->with('status', 'Testzustellung wurde ausgeführt. Status: '.$delivery->fresh()->status);
    }

    public function destroy(Tenant $tenant, Webhook $webhook): RedirectResponse
    {
        abort_unless($webhook->tenant_id === $tenant->id, 404);
        $webhook->delete();
        return back()->with('status', 'Webhook wurde gelöscht.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'endpoint' => ['required', 'url:https', 'max:2000'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => [Rule::in(['system.test', 'trip.created', 'trip.updated', 'invoice.created', 'driver.status_changed'])],
        ]);
    }
}
