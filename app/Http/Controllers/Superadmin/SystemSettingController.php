<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function index(): View
    {
        $settings = SystemSetting::query()->pluck('value', 'key');
        return view('superadmin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_url' => ['nullable', 'url:https', 'max:500'],
            'company_name' => ['required', 'string', 'max:190'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'maintenance_message' => ['nullable', 'string', 'max:2000'],
        ]);

        foreach ($data as $key => $value) {
            SystemSetting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'is_secret' => false]);
        }
        $this->audit->log('superadmin.settings.updated', null, [], array_keys($data));
        return back()->with('status', 'Globale Einstellungen wurden gespeichert.');
    }
}
