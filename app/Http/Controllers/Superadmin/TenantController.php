<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SaasPackage;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\AuditService;
use App\Services\SubscriptionService;
use App\Services\TenantProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TenantController extends Controller
{
    private const RESERVED_SLUGS = [
        'login',
        'logout',
        'install',
        'superadmin',
        'account',
        '2fa',
        'api',
        'up',
        'assets',
        'storage',
        'taxi-control',
    ];

    public function __construct(
        private readonly TenantProvisioningService $provisioning,
        private readonly SubscriptionService $subscriptions,
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
            'subscription' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $packageId = isset($data['saas_package_id']) ? (int) $data['saas_package_id'] : null;
        $billingCycle = $data['billing_cycle'];
        unset($data['saas_package_id'], $data['billing_cycle']);
        $data['slug'] = $this->validatedSlug($data);

        $tenant = DB::transaction(function () use ($data, $packageId, $billingCycle): Tenant {
            $tenant = Tenant::query()->create($data);
            $this->provisioning->provisionDefaults($tenant);
            if ($packageId) {
                $this->subscriptions->assign($tenant, $packageId, $billingCycle);
            }
            return $tenant;
        });

        $this->audit->log('superadmin.tenant.created', $tenant, [], $tenant->only(['name', 'slug', 'status']), ['package_id' => $packageId], $tenant->id);
        return redirect()->route('taxi-control.superadmin.tenants.edit', $tenant)->with('status', 'Mandant wurde angelegt und mit Standardrollen provisioniert.');
    }

    public function edit(Tenant $tenant): View
    {
        $subscription = Subscription::query()->withoutGlobalScopes()->where('tenant_id', $tenant->id)->latest('id')->first();
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
        $packageId = isset($data['saas_package_id']) ? (int) $data['saas_package_id'] : null;
        $billingCycle = $data['billing_cycle'];
        unset($data['saas_package_id'], $data['billing_cycle']);
        $data['slug'] = $this->validatedSlug($data, $tenant);

        DB::transaction(function () use ($tenant, $data, $packageId, $billingCycle): void {
            $tenant->update($data);
            $this->subscriptions->assign($tenant, $packageId, $billingCycle);
        });

        $this->audit->log('superadmin.tenant.updated', $tenant, $before, $tenant->fresh()->only(array_keys($before)), ['package_id' => $packageId, 'billing_cycle' => $billingCycle], $tenant->id);
        return back()->with('status', 'Mandant und Abonnement wurden gespeichert.');
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
            'saas_package_id' => ['nullable', 'integer', Rule::exists('saas_packages', 'id')->where('is_active', true)],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
        ]);
    }

    private function validatedSlug(array $data, ?Tenant $tenant = null): string
    {
        $slug = Str::slug((string) ($data['slug'] ?: $data['name']));

        if ($slug === '' || in_array($slug, self::RESERVED_SLUGS, true)) {
            throw ValidationException::withMessages([
                'slug' => 'Dieser Mandantenpfad ist für Taxi-Control reserviert. Bitte wählen Sie einen anderen Slug.',
            ]);
        }

        validator(
            ['slug' => $slug],
            ['slug' => ['required', 'max:100', Rule::unique('tenants', 'slug')->ignore($tenant?->id)]],
        )->validate();

        return $slug;
    }
}
