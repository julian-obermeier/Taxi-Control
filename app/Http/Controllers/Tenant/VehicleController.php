<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function index(): View
    {
        return view('tenant.vehicles.index', [
            'tenant' => $this->context->requireTenant(),
            'vehicles' => Vehicle::query()->orderBy('fleet_number')->paginate(30),
        ]);
    }

    public function create(): View
    {
        return view('tenant.vehicles.form', ['tenant' => $this->context->requireTenant(), 'vehicle' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->context->requireTenant();
        $data = $this->validateVehicle($request, $tenant->id);
        $vehicle = Vehicle::query()->create($this->payload($data));

        return redirect()->route('taxi-control.tenant.vehicles.index', ['tenant' => $tenant->slug])
            ->with('status', 'Fahrzeug '.$vehicle->fleet_number.' wurde angelegt.');
    }

    public function edit(Request $request): View
    {
        return view('tenant.vehicles.form', [
            'tenant' => $this->context->requireTenant(),
            'vehicle' => $this->findVehicle($request),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = $this->context->requireTenant();
        $vehicle = $this->findVehicle($request);
        $data = $this->validateVehicle($request, $tenant->id, $vehicle->id);
        $vehicle->update($this->payload($data));

        return redirect()->route('taxi-control.tenant.vehicles.index', ['tenant' => $tenant->slug])
            ->with('status', 'Fahrzeug wurde aktualisiert.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $tenant = $this->context->requireTenant();
        $vehicle = $this->findVehicle($request);

        if ($vehicle->trips()->exists() || $vehicle->locations()->exists()) {
            throw ValidationException::withMessages(['vehicle' => 'Das Fahrzeug besitzt bereits Fahrten oder Positionsdaten und kann deshalb nicht gelöscht werden. Deaktivieren Sie es stattdessen.']);
        }

        $vehicle->delete();

        return redirect()->route('taxi-control.tenant.vehicles.index', ['tenant' => $tenant->slug])
            ->with('status', 'Fahrzeug wurde gelöscht.');
    }

    private function findVehicle(Request $request): Vehicle
    {
        return Vehicle::query()->findOrFail((int) $request->route('vehicleId'));
    }

    private function validateVehicle(Request $request, int $tenantId, ?int $ignoreId = null): array
    {
        $fleetRule = Rule::unique('vehicles', 'fleet_number')->where(fn ($query) => $query->where('tenant_id', $tenantId));
        $plateRule = Rule::unique('vehicles', 'license_plate')->where(fn ($query) => $query->where('tenant_id', $tenantId));
        if ($ignoreId !== null) {
            $fleetRule->ignore($ignoreId);
            $plateRule->ignore($ignoreId);
        }

        return $request->validate([
            'fleet_number' => ['required', 'string', 'max:50', $fleetRule],
            'license_plate' => ['required', 'string', 'max:30', $plateRule],
            'vehicle_class' => ['required', 'string', 'max:50'],
            'seats' => ['required', 'integer', 'between:1,99'],
            'equipment' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['available', 'busy', 'maintenance', 'offline'])],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function payload(array $data): array
    {
        $equipment = array_values(array_filter(array_map('trim', explode(',', $data['equipment'] ?? ''))));

        return [
            'fleet_number' => $data['fleet_number'],
            'license_plate' => strtoupper($data['license_plate']),
            'vehicle_class' => $data['vehicle_class'],
            'seats' => $data['seats'],
            'equipment' => $equipment ?: null,
            'status' => $data['status'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }
}
