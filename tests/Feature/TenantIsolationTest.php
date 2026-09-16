<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_models_are_fail_closed_and_scoped_to_active_tenant(): void
    {
        $a = Tenant::query()->create(['name' => 'A', 'slug' => 'a', 'status' => 'active']);
        $b = Tenant::query()->create(['name' => 'B', 'slug' => 'b', 'status' => 'active']);
        $context = app(TenantContext::class);

        $context->run($a, fn () => Role::query()->create(['name' => 'Admin A', 'slug' => 'admin-a']));
        $context->run($b, fn () => Role::query()->create(['name' => 'Admin B', 'slug' => 'admin-b']));

        $this->assertSame(0, Role::query()->count());
        $context->set($a);
        $this->assertSame(['Admin A'], Role::query()->pluck('name')->all());
        $context->set($b);
        $this->assertSame(['Admin B'], Role::query()->pluck('name')->all());
        $context->clear();
    }

    public function test_tenant_id_cannot_be_changed_after_creation(): void
    {
        $a = Tenant::query()->create(['name' => 'A', 'slug' => 'a', 'status' => 'active']);
        $b = Tenant::query()->create(['name' => 'B', 'slug' => 'b', 'status' => 'active']);
        $context = app(TenantContext::class);
        $role = $context->run($a, fn () => Role::query()->create(['name' => 'Rolle', 'slug' => 'rolle']));

        $this->expectException(LogicException::class);
        $context->run($a, function () use ($role, $b): void {
            $role->tenant_id = $b->id;
            $role->save();
        });
    }
}
