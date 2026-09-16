<?php

namespace Tests\Feature;

use App\Models\SaasPackage;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_change_closes_old_subscription_and_creates_history(): void
    {
        SystemSetting::query()->create(['key' => 'trial_days', 'value' => '7']);
        $tenant = Tenant::query()->create(['name' => 'Taxi A', 'slug' => 'taxi-a', 'status' => 'trial']);
        $a = SaasPackage::query()->create(['name' => 'Basic', 'slug' => 'basic', 'is_active' => true]);
        $b = SaasPackage::query()->create(['name' => 'Premium', 'slug' => 'premium', 'is_active' => true]);
        $service = app(SubscriptionService::class);

        $first = $service->assign($tenant, $a->id, 'monthly');
        $this->assertSame('trial', $first->status);
        $this->assertNotNull($first->trial_ends_at);

        $second = $service->assign($tenant, $b->id, 'yearly');
        $this->assertSame($b->id, $second->saas_package_id);
        $this->assertSame('yearly', $second->billing_cycle);
        $this->assertSame(2, Subscription::query()->withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
        $this->assertSame('cancelled', $first->fresh()->status);
        $this->assertNotNull($first->fresh()->ends_at);
    }

    public function test_removing_package_ends_current_subscription(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Taxi B', 'slug' => 'taxi-b', 'status' => 'active']);
        $package = SaasPackage::query()->create(['name' => 'Basic', 'slug' => 'basic', 'is_active' => true]);
        $service = app(SubscriptionService::class);
        $subscription = $service->assign($tenant, $package->id);
        $service->assign($tenant, null);
        $this->assertSame('cancelled', $subscription->fresh()->status);
        $this->assertNotNull($subscription->fresh()->ends_at);
    }
}
