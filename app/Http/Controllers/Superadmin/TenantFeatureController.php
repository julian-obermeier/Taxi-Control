<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantFeatureController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function edit(Tenant $tenant): View
    {
        $subscription = Subscription::query()->withoutGlobalScopes()->where('tenant_id', $tenant->id)->latest()->first();
        $packageFeatureIds = $subscription?->saas_package_id
            ? DB::table('feature_saas_package')->where('saas_package_id', $subscription->saas_package_id)->where('enabled', true)->pluck('feature_id')->all()
            : [];
        $overrides = DB::table('tenant_feature_overrides')->where('tenant_id', $tenant->id)->get()->keyBy('feature_id');
        return view('superadmin.tenants.features', [
            'tenant' => $tenant,
            'features' => Feature::query()->orderBy('name')->get(),
            'packageFeatureIds' => $packageFeatureIds,
            'overrides' => $overrides,
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'features' => ['array'],
            'features.*.mode' => ['required', Rule::in(['inherit', 'enabled', 'disabled'])],
            'features.*.limit' => ['nullable', 'string', 'max:100'],
        ]);
        DB::transaction(function () use ($data, $tenant): void {
            DB::table('tenant_feature_overrides')->where('tenant_id', $tenant->id)->delete();
            foreach ($data['features'] ?? [] as $featureId => $config) {
                if ($config['mode'] === 'inherit') continue;
                abort_unless(Feature::query()->whereKey((int) $featureId)->exists(), 422, 'Unbekanntes Feature.');
                DB::table('tenant_feature_overrides')->insert([
                    'tenant_id' => $tenant->id,
                    'feature_id' => (int) $featureId,
                    'enabled' => $config['mode'] === 'enabled',
                    'limit_value' => $config['limit'] ?: null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
        $this->audit->log('superadmin.tenant.features.updated', $tenant, [], ['override_count' => count(array_filter($data['features'] ?? [], fn ($v) => $v['mode'] !== 'inherit'))], [], $tenant->id);
        return back()->with('status', 'Feature-Overrides wurden gespeichert.');
    }
}
