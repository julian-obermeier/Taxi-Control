<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Tenancy\TenantContext;

final class SubscriptionService
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function assign(Tenant $tenant, ?int $packageId, string $billingCycle = 'monthly'): ?Subscription
    {
        return $this->context->run($tenant, function () use ($packageId, $billingCycle): ?Subscription {
            $current = Subscription::query()->whereIn('status', ['trial', 'active'])->latest('id')->first();

            if ($packageId === null) {
                if ($current) {
                    $current->update(['status' => 'cancelled', 'ends_at' => now(), 'renews_at' => null]);
                }
                return null;
            }

            if ($current && (int) $current->saas_package_id === $packageId) {
                $current->update(['billing_cycle' => $billingCycle]);
                return $current->fresh();
            }

            if ($current) {
                $current->update(['status' => 'cancelled', 'ends_at' => now(), 'renews_at' => null]);
            }

            $trialDays = max(0, min(365, (int) (SystemSetting::query()->where('key', 'trial_days')->value('value') ?? 14)));
            $trial = $trialDays > 0;

            return Subscription::query()->create([
                'saas_package_id' => $packageId,
                'status' => $trial ? 'trial' : 'active',
                'starts_at' => now(),
                'trial_ends_at' => $trial ? now()->addDays($trialDays) : null,
                'billing_cycle' => $billingCycle,
            ]);
        });
    }
}
