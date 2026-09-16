<?php

namespace Tests\Feature;

use App\Models\Feature;
use App\Models\SaasPackage;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\FeatureService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FeatureServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_override_has_priority_over_package(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Taxi A', 'slug' => 'taxi-a', 'status' => 'active']);
        $feature = Feature::query()->create(['key' => 'dispatch', 'name' => 'Disposition', 'is_active' => true]);
        $package = SaasPackage::query()->create(['name' => 'Basic', 'slug' => 'basic', 'is_active' => true]);
        DB::table('feature_saas_package')->insert(['feature_id' => $feature->id, 'saas_package_id' => $package->id, 'enabled' => true]);

        $context = app(TenantContext::class);
        $context->set($tenant);
        Subscription::query()->create(['saas_package_id' => $package->id, 'status' => 'active', 'starts_at' => now(), 'billing_cycle' => 'monthly']);
        $service = app(FeatureService::class);
        $this->assertTrue($service->enabled('dispatch'));

        DB::table('tenant_feature_overrides')->insert(['tenant_id' => $tenant->id, 'feature_id' => $feature->id, 'enabled' => false, 'created_at' => now(), 'updated_at' => now()]);
        $this->assertFalse($service->enabled('dispatch'));
        $this->assertSame('tenant_override', $service->resolve('dispatch')['source']);
        $context->clear();
    }

    public function test_expired_trial_does_not_enable_package_features(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Taxi Trial', 'slug' => 'taxi-trial', 'status' => 'trial']);
        $feature = Feature::query()->create(['key' => 'crm', 'name' => 'CRM', 'is_active' => true]);
        $package = SaasPackage::query()->create(['name' => 'Trial', 'slug' => 'trial', 'is_active' => true]);
        DB::table('feature_saas_package')->insert(['feature_id' => $feature->id, 'saas_package_id' => $package->id, 'enabled' => true]);
        $context = app(TenantContext::class);
        $context->set($tenant);
        Subscription::query()->create(['saas_package_id' => $package->id, 'status' => 'trial', 'starts_at' => now()->subDays(20), 'trial_ends_at' => now()->subDay(), 'billing_cycle' => 'monthly']);
        $this->assertFalse(app(FeatureService::class)->enabled('crm'));
        $context->clear();
    }

    public function test_missing_or_unsubscribed_feature_fails_closed(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Taxi B', 'slug' => 'taxi-b', 'status' => 'active']);
        app(TenantContext::class)->set($tenant);
        $this->assertFalse(app(FeatureService::class)->enabled('does_not_exist'));
        app(TenantContext::class)->clear();
    }
}
