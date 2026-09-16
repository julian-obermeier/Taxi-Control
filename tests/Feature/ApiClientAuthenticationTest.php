<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApiClientAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_api_key_is_bound_to_its_tenant(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Taxi API',
            'slug' => 'taxi-api',
            'status' => 'active',
        ]);

        $secret = 'api-secret-for-test';
        DB::table('api_clients')->insert([
            'tenant_id' => $tenant->id,
            'name' => 'Integrationstest',
            'public_key' => 'tc_test_key',
            'secret_hash' => hash('sha256', $secret),
            'scopes' => json_encode(['read'], JSON_THROW_ON_ERROR),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer tc_test_key.'.$secret)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('tenant.id', $tenant->id)
            ->assertJsonPath('tenant.slug', $tenant->slug)
            ->assertJsonPath('client.name', 'Integrationstest');
    }

    public function test_api_key_without_required_scope_is_rejected(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Taxi API Scope',
            'slug' => 'taxi-api-scope',
            'status' => 'active',
        ]);

        $secret = 'scope-secret';
        DB::table('api_clients')->insert([
            'tenant_id' => $tenant->id,
            'name' => 'Ohne Leserecht',
            'public_key' => 'tc_scope_key',
            'secret_hash' => hash('sha256', $secret),
            'scopes' => json_encode(['write'], JSON_THROW_ON_ERROR),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer tc_scope_key.'.$secret)
            ->getJson('/api/v1/me')
            ->assertForbidden()
            ->assertJsonPath('message', 'API-Berechtigung fehlt.');
    }

    public function test_api_key_of_suspended_tenant_is_rejected(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Taxi Gesperrt',
            'slug' => 'taxi-gesperrt',
            'status' => 'suspended',
        ]);

        $secret = 'suspended-secret';
        DB::table('api_clients')->insert([
            'tenant_id' => $tenant->id,
            'name' => 'Gesperrte Integration',
            'public_key' => 'tc_suspended_key',
            'secret_hash' => hash('sha256', $secret),
            'scopes' => json_encode(['read'], JSON_THROW_ON_ERROR),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer tc_suspended_key.'.$secret)
            ->getJson('/api/v1/me')
            ->assertForbidden()
            ->assertJsonPath('message', 'Mandant ist nicht aktiv.');
    }
}
