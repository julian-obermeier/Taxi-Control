<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function index(Tenant $tenant): View
    {
        $checks = $this->checks($tenant);
        return view('tenant.onboarding.index', ['tenant' => $tenant, 'checks' => $checks, 'ready' => ! in_array(false, $checks, true)]);
    }

    public function complete(Tenant $tenant): RedirectResponse
    {
        $checks = $this->checks($tenant);
        if (in_array(false, $checks, true)) {
            return back()->withErrors(['onboarding' => 'Onboarding kann erst abgeschlossen werden, wenn alle Pflichtprüfungen erfüllt sind.']);
        }
        $tenant->forceFill(['onboarding_completed_at' => now()])->save();
        $this->audit->log('tenant.onboarding.completed', $tenant, [], ['onboarding_completed_at' => $tenant->onboarding_completed_at], [], $tenant->id);
        return back()->with('status', 'Onboarding wurde abgeschlossen.');
    }

    public function goLive(Tenant $tenant): RedirectResponse
    {
        if (! $tenant->onboarding_completed_at || in_array(false, $this->checks($tenant), true)) {
            return back()->withErrors(['go_live' => 'Go-Live-Prüfung ist noch nicht vollständig bestanden.']);
        }
        $tenant->forceFill(['go_live_at' => now(), 'status' => 'active'])->save();
        $this->audit->log('tenant.go_live', $tenant, [], ['go_live_at' => $tenant->go_live_at, 'status' => 'active'], [], $tenant->id);
        return back()->with('status', 'Mandant wurde für den Produktivbetrieb freigegeben.');
    }

    private function checks(Tenant $tenant): array
    {
        $settings = TenantSetting::query()->pluck('value', 'key');
        $owner = $tenant->users()->wherePivot('is_owner', true)->wherePivot('status', 'active')->first();
        return [
            'Unternehmensname vorhanden' => filled($tenant->name),
            'Aktiver Eigentümer vorhanden' => (bool) $owner,
            'Eigentümer mit 2FA abgesichert' => (bool) ($owner?->two_factor_confirmed_at),
            'SaaS-Paket zugeordnet' => Subscription::query()->whereIn('status', ['trial', 'active'])->exists(),
            'Kontakt-E-Mail gepflegt' => filled($settings['company_email'] ?? $tenant->billing_email),
            'Unternehmensort gepflegt' => filled($settings['city'] ?? null) && filled($settings['postal_code'] ?? null),
        ];
    }
}
