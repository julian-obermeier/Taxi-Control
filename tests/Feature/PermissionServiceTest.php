<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PermissionService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_deny_overrides_role_allow(): void
    {
        $tenant = Tenant::query()->create(['name' => 'A', 'slug' => 'a', 'status' => 'active']);
        $user = User::query()->create(['name' => 'User', 'email' => 'user@example.test', 'password' => 'secret-password']);
        DB::table('tenant_user')->insert(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'status' => 'active', 'is_owner' => false, 'created_at' => now(), 'updated_at' => now()]);
        $permission = Permission::query()->create(['key' => 'users.view', 'label' => 'Benutzer ansehen', 'module' => 'users', 'action' => 'view']);

        $context = app(TenantContext::class);
        $role = $context->run($tenant, fn () => Role::query()->create(['name' => 'Rolle', 'slug' => 'rolle']));
        DB::table('role_user')->insert(['tenant_id' => $tenant->id, 'role_id' => $role->id, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('role_permission')->insert(['tenant_id' => $tenant->id, 'role_id' => $role->id, 'permission_id' => $permission->id, 'created_at' => now(), 'updated_at' => now()]);

        $context->set($tenant);
        $service = app(PermissionService::class);
        $this->assertTrue($service->allows($user, 'users.view'));

        DB::table('user_permission_overrides')->insert(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'permission_id' => $permission->id, 'effect' => 'deny', 'created_at' => now(), 'updated_at' => now()]);
        $this->assertFalse($service->allows($user, 'users.view'));
        $context->clear();
    }
}
