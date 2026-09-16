<?php

namespace Tests\Feature;

use App\Enums\TripStatus;
use App\Models\Tenant;
use App\Models\Trip;
use App\Services\TripNumberService;
use App\Services\TripStateMachine;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DispatchCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_numbers_are_sequential_per_tenant_and_year(): void
    {
        $tenantA = Tenant::query()->create(['name' => 'Taxi A', 'slug' => 'taxi-a', 'status' => 'active']);
        $tenantB = Tenant::query()->create(['name' => 'Taxi B', 'slug' => 'taxi-b', 'status' => 'active']);
        $context = app(TenantContext::class);
        $numbers = app(TripNumberService::class);

        $context->run($tenantA, function () use ($numbers): void {
            $this->assertSame('202600001', $numbers->next(2026));
            $this->assertSame('202600002', $numbers->next(2026));
            $this->assertSame('202700001', $numbers->next(2027));
        });

        $context->run($tenantB, function () use ($numbers): void {
            $this->assertSame('202600001', $numbers->next(2026));
        });
    }

    public function test_trip_state_machine_allows_only_defined_transitions_and_records_history(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Taxi State', 'slug' => 'taxi-state', 'status' => 'active']);
        $context = app(TenantContext::class);

        $context->run($tenant, function (): void {
            $trip = Trip::query()->create([
                'order_number' => '202600001',
                'status' => TripStatus::Draft,
                'pickup_address' => 'Bahnhofstraße 1, 35390 Gießen',
            ]);

            $machine = app(TripStateMachine::class);
            $trip = $machine->transition($trip, TripStatus::New, source: 'test');
            $trip = $machine->transition($trip, TripStatus::ReadyForDispatch, source: 'test');

            $this->assertSame(TripStatus::ReadyForDispatch, $trip->status);
            $this->assertCount(2, $trip->stateEvents()->get());
            $this->assertSame('new', $trip->stateEvents()->latest('id')->first()->from_status);
            $this->assertSame('ready_for_dispatch', $trip->stateEvents()->latest('id')->first()->to_status);

            $this->expectException(ValidationException::class);
            $machine->transition($trip, TripStatus::Completed, source: 'test');
        });
    }
}
