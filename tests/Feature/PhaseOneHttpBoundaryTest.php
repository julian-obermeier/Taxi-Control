<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhaseOneHttpBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_cannot_open_another_tenant_by_slug(): void
    {
        $user = User::query()->create([
            'name' => 'Mandant A Benutzer',
            'email' => 'tenant-a@example.test',
            'password' => 'secret-password',
        ]);

        $tenantA = Tenant::query()->create([
            'name' => 'Taxi A',
            'slug' => 'taxi-a',
            'status' => 'active',
        ]);
        $tenantB = Tenant::query()->create([
            'name' => 'Taxi B',
            'slug' => 'taxi-b',
            'status' => 'active',
        ]);

        $tenantA->users()->attach($user->id, ['status' => 'active', 'is_owner' => true]);

        $this->actingAs($user)
            ->get(route('taxi-control.tenant.dashboard', ['tenant' => $tenantB->slug]))
            ->assertForbidden();
    }

    public function test_tenant_member_still_needs_explicit_route_permission(): void
    {
        $user = User::query()->create([
            'name' => 'Disponent',
            'email' => 'dispatcher@example.test',
            'password' => 'secret-password',
        ]);
        $tenant = Tenant::query()->create([
            'name' => 'Taxi Berechtigung',
            'slug' => 'taxi-berechtigung',
            'status' => 'active',
        ]);
        $tenant->users()->attach($user->id, ['status' => 'active', 'is_owner' => false]);

        $url = route('taxi-control.tenant.dashboard', ['tenant' => $tenant->slug]);
        $this->actingAs($user)->get($url)->assertForbidden();

        $permissionId = DB::table('permissions')->insertGetId([
            'key' => 'dashboard.view',
            'label' => 'Dashboard ansehen',
            'module' => 'dashboard',
            'action' => 'view',
            'data_scope' => 'tenant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $roleId = DB::table('roles')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'Testrolle',
            'slug' => 'testrolle',
            'is_system' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('role_permission')->insert([
            'tenant_id' => $tenant->id,
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('role_user')->insert([
            'tenant_id' => $tenant->id,
            'role_id' => $roleId,
            'user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->get($url)->assertOk();
    }

    public function test_non_superadmin_cannot_open_superadmin_area(): void
    {
        $user = User::query()->create([
            'name' => 'Normaler Benutzer',
            'email' => 'normal@example.test',
            'password' => 'secret-password',
            'is_superadmin' => false,
        ]);

        $this->actingAs($user)
            ->get(route('taxi-control.superadmin.dashboard'))
            ->assertForbidden();
    }

    public function test_enabled_two_factor_blocks_protected_area_until_challenge_passed(): void
    {
        $user = User::query()->create([
            'name' => '2FA Benutzer',
            'email' => '2fa@example.test',
            'password' => 'secret-password',
            'two_factor_secret' => app(TotpService::class)->generateSecret(),
            'two_factor_enabled_at' => now(),
        ]);
        $tenant = Tenant::query()->create([
            'name' => 'Taxi 2FA',
            'slug' => 'taxi-2fa',
            'status' => 'active',
        ]);
        $tenant->users()->attach($user->id, ['status' => 'active', 'is_owner' => true]);

        $this->actingAs($user)
            ->get(route('taxi-control.home'))
            ->assertRedirect(route('taxi-control.2fa.challenge'));

        $this->withSession(['2fa_passed' => true])
            ->actingAs($user)
            ->get(route('taxi-control.home'))
            ->assertRedirect(route('taxi-control.tenant.dashboard', ['tenant' => $tenant->slug]));
    }
}
