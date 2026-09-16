<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\SaasPackage;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function index(): View
    {
        return view('superadmin.packages.index', ['packages' => SaasPackage::query()->withCount(['features', 'subscriptions'])->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('superadmin.packages.form', ['package' => new SaasPackage(['is_active' => true]), 'features' => Feature::query()->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $featureIds = $data['features'] ?? [];
        unset($data['features']);
        $data['slug'] = Str::slug($data['slug'] ?: $data['name']);
        $data['limits'] = ['users' => $data['limit_users'] ?? null, 'vehicles' => $data['limit_vehicles'] ?? null];
        unset($data['limit_users'], $data['limit_vehicles']);
        $package = SaasPackage::query()->create($data);
        $package->features()->sync($this->featureSync($featureIds));
        $this->audit->log('superadmin.package.created', $package, [], $package->toArray());
        return redirect()->route('taxi-control.superadmin.packages.edit', $package)->with('status', 'Paket wurde angelegt.');
    }

    public function edit(SaasPackage $package): View
    {
        $package->load('features');
        return view('superadmin.packages.form', ['package' => $package, 'features' => Feature::query()->orderBy('name')->get()]);
    }

    public function update(Request $request, SaasPackage $package): RedirectResponse
    {
        $before = $package->toArray();
        $data = $this->validated($request, $package);
        $featureIds = $data['features'] ?? [];
        unset($data['features']);
        $data['slug'] = Str::slug($data['slug'] ?: $data['name']);
        $data['limits'] = ['users' => $data['limit_users'] ?? null, 'vehicles' => $data['limit_vehicles'] ?? null];
        unset($data['limit_users'], $data['limit_vehicles']);
        $package->update($data);
        $package->features()->sync($this->featureSync($featureIds));
        $this->audit->log('superadmin.package.updated', $package, $before, $package->fresh()->toArray());
        return back()->with('status', 'Paket wurde gespeichert.');
    }

    public function destroy(SaasPackage $package): RedirectResponse
    {
        if ($package->subscriptions()->exists()) {
            return back()->withErrors(['package' => 'Das Paket wird von mindestens einem Mandanten verwendet und kann nicht gelöscht werden.']);
        }
        $package->delete();
        return redirect()->route('taxi-control.superadmin.packages.index')->with('status', 'Paket wurde gelöscht.');
    }

    private function validated(Request $request, ?SaasPackage $package = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/', Rule::unique('saas_packages', 'slug')->ignore($package?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'price_monthly' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'price_yearly' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'is_active' => ['required', 'boolean'],
            'limit_users' => ['nullable', 'integer', 'min:1'],
            'limit_vehicles' => ['nullable', 'integer', 'min:1'],
            'features' => ['array'],
            'features.*' => ['integer', 'exists:features,id'],
        ]);
    }

    private function featureSync(array $ids): array
    {
        return collect($ids)->mapWithKeys(fn ($id) => [(int) $id => ['enabled' => true]])->all();
    }
}
