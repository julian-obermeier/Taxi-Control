<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Tenancy\TenantContext;
use Illuminate\View\View;

class DispatchController extends Controller
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function __invoke(): View
    {
        $tenant = $this->context->requireTenant();
        $terminal = array_map(static fn (TripStatus $status): string => $status->value, array_filter(TripStatus::cases(), static fn (TripStatus $status): bool => $status->terminal()));

        $trips = Trip::query()
            ->with(['type', 'driver', 'vehicle'])
            ->whereNotIn('status', $terminal)
            ->orderByDesc('priority')
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->limit(100)
            ->get();

        $drivers = Driver::query()->where('is_active', true)->orderBy('display_name')->get();
        $vehicles = Vehicle::query()->where('is_active', true)->orderBy('fleet_number')->get();

        return view('tenant.dispatch.index', [
            'tenant' => $tenant,
            'trips' => $trips,
            'drivers' => $drivers,
            'vehicles' => $vehicles,
            'metrics' => [
                'open' => $trips->count(),
                'unassigned' => $trips->whereNull('driver_id')->count(),
                'driversReady' => $drivers->whereIn('status', ['available', 'ready'])->count(),
                'vehiclesReady' => $vehicles->where('status', 'available')->count(),
            ],
        ]);
    }
}
