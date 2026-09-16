<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function index(Tenant $tenant): View
    {
        $settings = TenantSetting::query()->pluck('value', 'key');
        return view('tenant.settings.index', compact('tenant', 'settings'));
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'company_phone' => ['nullable', 'string', 'max:80'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'street' => ['nullable', 'string', 'max:190'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['required', 'string', 'size:2'],
            'dispatch_name' => ['nullable', 'string', 'max:160'],
        ]);
        foreach ($data as $key => $value) {
            TenantSetting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'is_secret' => false]);
        }
        $this->audit->log('tenant.settings.updated', $tenant, [], array_keys($data), [], $tenant->id);
        return back()->with('status', 'Einstellungen wurden gespeichert.');
    }
}
