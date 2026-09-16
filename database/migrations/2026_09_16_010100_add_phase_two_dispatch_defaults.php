<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        $permissions = [
            'dispatch.view' => ['Disposition ansehen', 'dispatch', 'view'],
            'dispatch.create' => ['Fahrten anlegen', 'dispatch', 'create'],
            'dispatch.update' => ['Fahrten bearbeiten', 'dispatch', 'update'],
            'dispatch.assign' => ['Fahrer und Fahrzeuge zuweisen', 'dispatch', 'assign'],
            'dispatch.status' => ['Fahrtstatus ändern', 'dispatch', 'status'],
            'dispatch.delete' => ['Fahrten löschen', 'dispatch', 'delete'],
            'drivers.view' => ['Fahrer ansehen', 'drivers', 'view'],
            'drivers.create' => ['Fahrer anlegen', 'drivers', 'create'],
            'drivers.update' => ['Fahrer bearbeiten', 'drivers', 'update'],
            'drivers.delete' => ['Fahrer löschen', 'drivers', 'delete'],
            'drivers.location' => ['Fahrerpositionen verarbeiten', 'drivers', 'location'],
            'vehicles.view' => ['Fahrzeuge ansehen', 'vehicles', 'view'],
            'vehicles.create' => ['Fahrzeuge anlegen', 'vehicles', 'create'],
            'vehicles.update' => ['Fahrzeuge bearbeiten', 'vehicles', 'update'],
            'vehicles.delete' => ['Fahrzeuge löschen', 'vehicles', 'delete'],
            'driver_portal.view' => ['Eigenes Fahrerportal öffnen', 'driver_portal', 'view'],
            'driver_portal.update' => ['Eigene Aufträge bearbeiten', 'driver_portal', 'update'],
            'driver_portal.location' => ['Eigene Position übermitteln', 'driver_portal', 'location'],
        ];

        foreach ($permissions as $key => [$label, $module, $action]) {
            DB::table('permissions')->updateOrInsert(
                ['key' => $key],
                [
                    'label' => $label,
                    'module' => $module,
                    'action' => $action,
                    'data_scope' => $module === 'driver_portal' ? 'own' : 'tenant',
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $permissionIds = DB::table('permissions')->whereIn('key', array_keys($permissions))->pluck('id', 'key');
        $roleDefinitions = [
            'unternehmensadministrator' => array_keys($permissions),
            'disponent' => [
                'dispatch.view', 'dispatch.create', 'dispatch.update', 'dispatch.assign', 'dispatch.status',
                'drivers.view', 'drivers.create', 'drivers.update', 'vehicles.view', 'vehicles.create', 'vehicles.update',
            ],
            'fahrer' => ['driver_portal.view', 'driver_portal.update', 'driver_portal.location'],
        ];

        foreach (DB::table('roles')->whereIn('slug', array_keys($roleDefinitions))->get() as $role) {
            foreach ($roleDefinitions[$role->slug] as $key) {
                $permissionId = $permissionIds[$key] ?? null;
                if ($permissionId === null) {
                    continue;
                }
                DB::table('role_permission')->insertOrIgnore([
                    'tenant_id' => $role->tenant_id,
                    'role_id' => $role->id,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $tripTypes = [
            ['name' => 'Standardfahrt', 'slug' => 'standardfahrt', 'default_priority' => 50],
            ['name' => 'Vorbestellung', 'slug' => 'vorbestellung', 'default_priority' => 45],
            ['name' => 'Flughafentransfer', 'slug' => 'flughafentransfer', 'default_priority' => 55],
        ];

        foreach (DB::table('tenants')->pluck('id') as $tenantId) {
            foreach ($tripTypes as $type) {
                DB::table('trip_types')->updateOrInsert(
                    ['tenant_id' => $tenantId, 'slug' => $type['slug']],
                    [
                        'name' => $type['name'],
                        'default_priority' => $type['default_priority'],
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        $keys = [
            'dispatch.view', 'dispatch.create', 'dispatch.update', 'dispatch.assign', 'dispatch.status', 'dispatch.delete',
            'drivers.view', 'drivers.create', 'drivers.update', 'drivers.delete', 'drivers.location',
            'vehicles.view', 'vehicles.create', 'vehicles.update', 'vehicles.delete',
            'driver_portal.view', 'driver_portal.update', 'driver_portal.location',
        ];

        DB::table('trip_types')->whereIn('slug', ['standardfahrt', 'vorbestellung', 'flughafentransfer'])->delete();
        DB::table('permissions')->whereIn('key', $keys)->delete();
    }
};
