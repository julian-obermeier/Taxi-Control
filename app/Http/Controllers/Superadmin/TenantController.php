<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SaasPackage;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\AuditService;
use App\Services\TenantProvisioningService;
use App\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantProvisioningService $provisioning,
        private readonly TenantContext $context,
        private readonly AuditService $audit,
    ) {
    }

    public function index(Request $request): View
    {
        $query = Tenant::query()->latest();
        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"));
        }

        return view('superadmin.tenants.index', ['tenants' => $query->paginate(20)->withQueryString()]);
    }

    public function create(): View
    {
        return view('superadmin.tenants.form', [
            'tenant' => new Tenant(['timezone' => 'Europe/Berlin', 'locale' => 'de', 'status' => 'trial']),
            'packages' => SaasPackage::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $packageId = $data['saas_package_id'] ?? null;
        unset($data['saas_package_id']);
        $data['slug'] = Str::slug($data['slug'] ?: $data['name']);

        $tenant = DB::transaction(function () use ($data, $packageId): Tenant {
            $tenant = Tenant::query()->create($data);
            $this->provisioning->provisionDefaults($tenant);

            if ($packageId) {
                $this->context->run($tenant, fn () => Subscription::query()->create([
                    'saas_package_id' => $packageId,
                    'status' => 'trial',
                    'starts_at' => now(),
                    'trial_ends_at' => now()->addDays(14),
                    'billing_cycle' => 'monthly',
                ]));
            }

            return $tenant;
        });

        $this->audit->log('superadmin.tenant.created', $tenant, [], $tenant->only(['name', 'slug', 'status']), [], $tenant->id);

        return redirect()->route('taxi-control.superadmin.tenants.edit', $tenant)->with('status', 'Mandant wurde angelegt und mit Standardrollen provisioniert.');
    }

    public function edit(Tenant $tenant): View
    {
        $subscription = Subscription::query()->withoutGlobalScopes()->where('tenant_id', $tenant->id)->latest()->first();

        return view('superadmin.tenants.form', [
            'tenant' => $tenant,
            'subscription' => $subscription,
            'packages' => SaasPackage::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $before = $tenant->only(['name', 'legal_name', 'slug', 'status', 'billing_email', 'timezone']);
        $data = $this->validated($request, $tenant);
        $packageId = $data['saas_package_id'] ?? null;
        unset($data['saas_package_id']);
        $data['slug'] = Str::slug($data['slug'] ?: $data['name']);
        $tenant->update($data);

        $this->context->run($tenant, function () use ($packageId): void {
            $subscription = Subscription::query()->latest()->first();
            if ($packageId) {
                if ($subscription) {
                    $subscription->update(['saas_package_id' => $packageId]);
                } else {
                    Subscription::query()->create([
                        'saas_package_id' => $packageId,
                        'status' => 'trial',
                        'starts_at' => now(),
                        'trial_ends_at' => now()->addDays(14),
                        'billing_cycle' => 'monthly',
                    ]);
                }
            }
        });

        $this->audit->log('superadmin.tenant.updated', $tenant, $before, $tenant->only(array_keys($before)), [], $tenant->id);

        return back()->with('status', 'Mandant wurde gespeichert.');
    }

    public function destroy(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->validate(['confirmation' => ['required', Rule::in([$tenant->slug])]]);
        $snapshot = $tenant->only(['id', 'name', 'slug']);
        $tenant->delete();
        $this->audit->log('superadmin.tenant.deleted', null, $snapshot, [], ['deleted_tenant_id' => $snapshot['id']]);

        return redirect()->route('taxi-control.superadmin.tenants.index')->with('status', 'Mandant wurde gelöscht.');
    }

    private function validated(Request $request, ?Tenant $tenant = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'legal_name' => ['nullable', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', Rule::unique('tenants', 'slug')->ignore($tenant?->id)],
            'status' => ['required', Rule::in(['trial', 'active', 'suspended', 'cancelled'])],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'timezone' => ['required', Rule::in(['Europe/Berlin'])],
            'locale' => ['required', Rule::in(['de'])],
            'saas_package_id' => ['nullable', 'integer', 'exists:saas_packages,id'],
        ]);
    }
}
