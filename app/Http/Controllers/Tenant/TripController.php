<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\TripType;
use App\Models\Vehicle;
use App\Services\TripAssignmentService;
use App\Services\TripNumberService;
use App\Services\TripStateMachine;
use App\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TripController extends Controller
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function index(Request $request): View
    {
        $tenant = $this->context->requireTenant();
        $query = Trip::query()->with(['type', 'driver', 'vehicle'])->latest('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }
        if ($request->filled('q')) {
            $term = '%'.trim((string) $request->string('q')).'%';
            $query->where(function ($builder) use ($term): void {
                $builder->where('order_number', 'like', $term)
                    ->orWhere('passenger_name', 'like', $term)
                    ->orWhere('pickup_address', 'like', $term)
                    ->orWhere('destination_address', 'like', $term);
            });
        }

        return view('tenant.trips.index', [
            'tenant' => $tenant,
            'trips' => $query->paginate(30)->withQueryString(),
            'statuses' => TripStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('tenant.trips.form', $this->formData(null));
    }

    public function store(Request $request, TripNumberService $numbers): RedirectResponse
    {
        $tenant = $this->context->requireTenant();
        $data = $this->validateTrip($request, $tenant->id);
        $type = ! empty($data['trip_type_id']) ? TripType::query()->findOrFail($data['trip_type_id']) : null;
        $scheduledAt = ! empty($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null;
        $status = $scheduledAt !== null && $scheduledAt->isFuture() ? TripStatus::Reserved : TripStatus::ReadyForDispatch;

        $trip = Trip::query()->create([
            'order_number' => $numbers->next(),
            'trip_type_id' => $type?->id,
            'status' => $status,
            'dispatch_mode' => 'manual',
            'priority' => $data['priority'] ?? $type?->default_priority ?? 50,
            'passenger_name' => $data['passenger_name'] ?? null,
            'passenger_phone' => $data['passenger_phone'] ?? null,
            'passenger_count' => $data['passenger_count'] ?? 1,
            'vehicle_class' => $data['vehicle_class'] ?? null,
            'pickup_address' => $data['pickup_address'],
            'destination_address' => $data['destination_address'] ?? null,
            'requested_at' => now(),
            'scheduled_at' => $scheduledAt,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('taxi-control.tenant.trips.show', ['tenant' => $tenant->slug, 'tripId' => $trip->id])
            ->with('status', 'Fahrt '.$trip->order_number.' wurde angelegt.');
    }

    public function show(Request $request, TripStateMachine $stateMachine): View
    {
        $tenant = $this->context->requireTenant();
        $trip = $this->findTrip($request)->load(['type', 'driver', 'vehicle', 'stops', 'stateEvents']);

        return view('tenant.trips.show', [
            'tenant' => $tenant,
            'trip' => $trip,
            'drivers' => Driver::query()->where('is_active', true)->orderBy('display_name')->get(),
            'vehicles' => Vehicle::query()->where('is_active', true)->orderBy('fleet_number')->get(),
            'allowedStatuses' => $stateMachine->allowedTargets($trip->status),
        ]);
    }

    public function edit(Request $request): View
    {
        $trip = $this->findTrip($request);
        if ($trip->status->terminal()) {
            throw ValidationException::withMessages(['trip' => 'Abgeschlossene oder stornierte Fahrten können nicht mehr bearbeitet werden.']);
        }

        return view('tenant.trips.form', $this->formData($trip));
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = $this->context->requireTenant();
        $trip = $this->findTrip($request);
        if ($trip->status->terminal()) {
            throw ValidationException::withMessages(['trip' => 'Abgeschlossene oder stornierte Fahrten können nicht mehr bearbeitet werden.']);
        }

        $data = $this->validateTrip($request, $tenant->id);
        $type = ! empty($data['trip_type_id']) ? TripType::query()->findOrFail($data['trip_type_id']) : null;

        $trip->update([
            'trip_type_id' => $type?->id,
            'priority' => $data['priority'] ?? $type?->default_priority ?? $trip->priority,
            'passenger_name' => $data['passenger_name'] ?? null,
            'passenger_phone' => $data['passenger_phone'] ?? null,
            'passenger_count' => $data['passenger_count'] ?? 1,
            'vehicle_class' => $data['vehicle_class'] ?? null,
            'pickup_address' => $data['pickup_address'],
            'destination_address' => $data['destination_address'] ?? null,
            'scheduled_at' => ! empty($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('taxi-control.tenant.trips.show', ['tenant' => $tenant->slug, 'tripId' => $trip->id])
            ->with('status', 'Fahrt wurde aktualisiert.');
    }

    public function assign(Request $request, TripAssignmentService $assignmentService): RedirectResponse
    {
        $tenant = $this->context->requireTenant();
        $trip = $this->findTrip($request);
        $data = $request->validate([
            'driver_id' => ['nullable', 'integer'],
            'vehicle_id' => ['nullable', 'integer'],
        ]);

        $assignmentService->assign(
            $trip,
            isset($data['driver_id']) ? (int) $data['driver_id'] : null,
            isset($data['vehicle_id']) ? (int) $data['vehicle_id'] : null,
            $request->user()?->id,
        );

        return redirect()->route('taxi-control.tenant.trips.show', ['tenant' => $tenant->slug, 'tripId' => $trip->id])
            ->with('status', 'Zuweisung wurde gespeichert.');
    }

    public function status(Request $request, TripStateMachine $stateMachine): RedirectResponse
    {
        $tenant = $this->context->requireTenant();
        $trip = $this->findTrip($request);
        $data = $request->validate([
            'status' => ['required', Rule::enum(TripStatus::class)],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $stateMachine->transition(
            $trip,
            TripStatus::from($data['status']),
            $request->user()?->id,
            'dispatch',
            $data['reason'] ?? null,
        );

        return redirect()->route('taxi-control.tenant.trips.show', ['tenant' => $tenant->slug, 'tripId' => $trip->id])
            ->with('status', 'Fahrtstatus wurde aktualisiert.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $tenant = $this->context->requireTenant();
        $trip = $this->findTrip($request);

        if ($trip->status !== TripStatus::Draft || $trip->stops()->exists() || $trip->stateEvents()->exists()) {
            throw ValidationException::withMessages(['trip' => 'Nur unverarbeitete Entwürfe ohne Folgeeinträge dürfen gelöscht werden.']);
        }

        $trip->delete();

        return redirect()->route('taxi-control.tenant.trips.index', ['tenant' => $tenant->slug])
            ->with('status', 'Entwurf wurde gelöscht.');
    }

    private function findTrip(Request $request): Trip
    {
        return Trip::query()->findOrFail((int) $request->route('tripId'));
    }

    private function formData(?Trip $trip): array
    {
        return [
            'tenant' => $this->context->requireTenant(),
            'trip' => $trip,
            'types' => TripType::query()->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function validateTrip(Request $request, int $tenantId): array
    {
        return $request->validate([
            'trip_type_id' => ['nullable', 'integer', Rule::exists('trip_types', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)->where('is_active', true))],
            'priority' => ['nullable', 'integer', 'between:1,100'],
            'passenger_name' => ['nullable', 'string', 'max:255'],
            'passenger_phone' => ['nullable', 'string', 'max:80'],
            'passenger_count' => ['required', 'integer', 'between:1,99'],
            'vehicle_class' => ['nullable', 'string', 'max:50'],
            'pickup_address' => ['required', 'string', 'max:1000'],
            'destination_address' => ['nullable', 'string', 'max:1000'],
            'scheduled_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
