<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TripType;
use App\Tenancy\TenantContext;

final class TenantProvisioningService
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function provisionDefaults(Tenant $tenant): void
    {
        $this->context->run($tenant, function () use ($tenant): void {
            $admin = Role::query()->create([
                'name' => 'Unternehmensadministrator',
                'slug' => 'unternehmensadministrator',
                'description' => 'Vollzugriff innerhalb des eigenen Taxiunternehmens.',
                'is_system' => true,
            ]);
            $dispatcher = Role::query()->create([
                'name' => 'Disponent',
                'slug' => 'disponent',
                'description' => 'Standardrolle für Leitstelle und Disposition.',
                'is_system' => true,
            ]);
            $driver = Role::query()->create([
                'name' => 'Fahrer',
                'slug' => 'fahrer',
                'description' => 'Standardrolle für Fahrerzugänge.',
                'is_system' => true,
            ]);

            $permissionIds = Permission::query()->pluck('id')->all();
            $admin->permissions()->syncWithPivotValues($permissionIds, ['tenant_id' => $tenant->id]);

            $dispatcherPermissions = Permission::query()
                ->whereIn('key', [
                    'dashboard.view',
                    'dispatch.view', 'dispatch.create', 'dispatch.update', 'dispatch.assign', 'dispatch.status',
                    'drivers.view', 'drivers.create', 'drivers.update',
                    'vehicles.view', 'vehicles.create', 'vehicles.update',
                ])
                ->pluck('id')
                ->all();
            $dispatcher->permissions()->syncWithPivotValues($dispatcherPermissions, ['tenant_id' => $tenant->id]);

            $driverPermissions = Permission::query()
                ->whereIn('key', ['driver_portal.view', 'driver_portal.update', 'driver_portal.location'])
                ->pluck('id')
                ->all();
            $driver->permissions()->syncWithPivotValues($driverPermissions, ['tenant_id' => $tenant->id]);

            foreach ([
                ['name' => 'Standardfahrt', 'slug' => 'standardfahrt', 'default_priority' => 50],
                ['name' => 'Vorbestellung', 'slug' => 'vorbestellung', 'default_priority' => 45],
                ['name' => 'Flughafentransfer', 'slug' => 'flughafentransfer', 'default_priority' => 55],
            ] as $definition) {
                TripType::query()->create([...$definition, 'is_active' => true]);
            }
        });
    }
}
