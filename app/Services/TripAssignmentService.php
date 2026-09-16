<?php

namespace App\Services;

use App\Enums\TripStatus;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TripAssignmentService
{
    public function __construct(private readonly TripStateMachine $stateMachine)
    {
    }

    public function assign(Trip $trip, ?int $driverId, ?int $vehicleId, ?int $userId = null): Trip
    {
        return DB::transaction(function () use ($trip, $driverId, $vehicleId, $userId): Trip {
            $locked = Trip::query()->whereKey($trip->id)->lockForUpdate()->firstOrFail();

            if ($locked->status->terminal()) {
                throw ValidationException::withMessages(['assignment' => 'Abgeschlossene oder stornierte Fahrten können nicht neu zugewiesen werden.']);
            }

            if (in_array($locked->status, [TripStatus::Draft, TripStatus::New, TripStatus::Reserved], true)) {
                throw ValidationException::withMessages(['assignment' => 'Die Fahrt muss zuerst zur Disposition freigegeben werden.']);
            }

            $driver = $driverId !== null
                ? Driver::query()->whereKey($driverId)->where('is_active', true)->firstOrFail()
                : null;
            $vehicle = $vehicleId !== null
                ? Vehicle::query()->whereKey($vehicleId)->where('is_active', true)->firstOrFail()
                : null;

            if ($vehicle !== null && $locked->vehicle_class !== null && $locked->vehicle_class !== '' && $vehicle->vehicle_class !== $locked->vehicle_class) {
                throw ValidationException::withMessages(['vehicle_id' => 'Das ausgewählte Fahrzeug entspricht nicht der geforderten Fahrzeugklasse.']);
            }

            if ($vehicle !== null && $vehicle->seats < $locked->passenger_count) {
                throw ValidationException::withMessages(['vehicle_id' => 'Das ausgewählte Fahrzeug hat zu wenige Sitzplätze.']);
            }

            if ($driver === null && $vehicle === null && $locked->status !== TripStatus::Assigned) {
                throw ValidationException::withMessages(['assignment' => 'Eine vollständige Aufhebung der Zuweisung ist nur aus dem Status „Zugewiesen“ möglich.']);
            }

            $locked->forceFill([
                'driver_id' => $driver?->id,
                'vehicle_id' => $vehicle?->id,
            ])->save();

            if (($driver !== null || $vehicle !== null) && in_array($locked->status, [TripStatus::ReadyForDispatch, TripStatus::SearchingDriver, TripStatus::OfferSent], true)) {
                return $this->stateMachine->transition($locked, TripStatus::Assigned, $userId, 'dispatch', null, [
                    'driver_id' => $driver?->id,
                    'vehicle_id' => $vehicle?->id,
                ]);
            }

            if ($driver === null && $vehicle === null && $locked->status === TripStatus::Assigned) {
                return $this->stateMachine->transition($locked, TripStatus::SearchingDriver, $userId, 'dispatch', 'Zuweisung aufgehoben');
            }

            return $locked->fresh();
        }, 3);
    }
}
