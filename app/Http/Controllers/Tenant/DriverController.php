<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function index(): View
    {
        return view('tenant.drivers.index', [
            'tenant' => $this->context->requireTenant(),
            'drivers' => Driver::query()->with('user')->orderBy('display_name')->paginate(30),
        ]);
    }

    public function create(): View
    {
        return view('tenant.drivers.form', $this->formData(null));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->context->requireTenant();
        $data = $this->validateDriver($request, $tenant->id);
        $driver = Driver::query()->create($this->payload($data));

        return redirect()->route('taxi-control.tenant.drivers.index', ['tenant' => $tenant->slug])
            ->with('status', 'Fahrer '.$driver->display_name.' wurde angelegt.');
    }

    public function edit(Request $request): View
    {
        return view('tenant.drivers.form', $this->formData($this->findDriver($request)));
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = $this->context->requireTenant();
        $driver = $this->findDriver($request);
        $data = $this->validateDriver($request, $tenant->id, $driver->id);
        $driver->update($this->payload($data));

        return redirect()->route('taxi-control.tenant.drivers.index', ['tenant' => $tenant->slug])
            ->with('status', 'Fahrer wurde aktualisiert.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $tenant = $this->context->requireTenant();
        $driver = $this->findDriver($request);

        if ($driver->trips()->exists() || $driver->locations()->exists()) {
            throw ValidationException::withMessages(['driver' => 'Der Fahrer besitzt bereits Fahrten oder Positionsdaten und kann deshalb nicht gelöscht werden. Deaktivieren Sie ihn stattdessen.']);
        }

        $driver->delete();

        return redirect()->route('taxi-control.tenant.drivers.index', ['tenant' => $tenant->slug])
            ->with('status', 'Fahrer wurde gelöscht.');
    }

    private function findDriver(Request $request): Driver
    {
        return Driver::query()->findOrFail((int) $request->route('driverId'));
    }

    private function formData(?Driver $driver): array
    {
        $tenant = $this->context->requireTenant();

        return [
            'tenant' => $tenant,
            'driver' => $driver,
            'users' => $tenant->users()->wherePivot('status', 'active')->orderBy('name')->get(),
        ];
    }

    private function validateDriver(Request $request, int $tenantId, ?int $ignoreId = null): array
    {
        $employeeRule = Rule::unique('drivers', 'employee_number')->where(fn ($query) => $query->where('tenant_id', $tenantId));
        if ($ignoreId !== null) {
            $employeeRule->ignore($ignoreId);
        }

        return $request->validate([
            'user_id' => ['nullable', 'integer', Rule::exists('tenant_user', 'user_id')->where(fn ($query) => $query->where('tenant_id', $tenantId)->where('status', 'active'))],
            'employee_number' => ['required', 'string', 'max:50', $employeeRule],
            'display_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
            'status' => ['required', Rule::in(['offline', 'available', 'ready', 'busy', 'break', 'unavailable'])],
            'qualifications' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function payload(array $data): array
    {
        $qualifications = array_values(array_filter(array_map('trim', explode(',', $data['qualifications'] ?? ''))));

        return [
            'user_id' => $data['user_id'] ?? null,
            'employee_number' => $data['employee_number'],
            'display_name' => $data['display_name'],
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'],
            'qualifications' => $qualifications ?: null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }
}
