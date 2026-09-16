<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Tenancy\TenantContext;

final class TenantProvisioningService
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function provisionDefaults(Tenant $tenant): void
    {
        $this->context->run($tenant, function (): void {
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
            Role::query()->create([
                'name' => 'Fahrer',
                'slug' => 'fahrer',
                'description' => 'Standardrolle für Fahrerzugänge.',
                'is_system' => true,
            ]);

            $permissionIds = Permission::query()->pluck('id')->all();
            $admin->permissions()->syncWithPivotValues($permissionIds, ['tenant_id' => $tenant->id]);

            $dispatcherPermissions = Permission::query()
                ->whereIn('key', ['dashboard.view'])
                ->pluck('id')
                ->all();
            $dispatcher->permissions()->syncWithPivotValues($dispatcherPermissions, ['tenant_id' => $tenant->id]);
        });
    }
}
