<?php

namespace App\Services;

use App\Models\Feature;
use App\Models\Subscription;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

final class FeatureService
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function enabled(string $featureKey): bool
    {
        return $this->resolve($featureKey)['enabled'];
    }

    public function limit(string $featureKey): ?string
    {
        return $this->resolve($featureKey)['limit'];
    }

    /** @return array{enabled: bool, limit: ?string, source: string} */
    public function resolve(string $featureKey): array
    {
        $tenantId = $this->context->requireId();
        $feature = Feature::query()->where('key', $featureKey)->where('is_active', true)->first();
        if (! $feature) {
            return ['enabled' => false, 'limit' => null, 'source' => 'missing'];
        }

        $override = DB::table('tenant_feature_overrides')
            ->where('tenant_id', $tenantId)
            ->where('feature_id', $feature->id)
            ->first();
        if ($override) {
            return ['enabled' => (bool) $override->enabled, 'limit' => $override->limit_value, 'source' => 'tenant_override'];
        }

        $subscription = Subscription::query()
            ->whereIn('status', ['trial', 'active'])
            ->where(function ($query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->latest('id')
            ->first();
        if (! $subscription?->saas_package_id) {
            return ['enabled' => false, 'limit' => null, 'source' => 'no_subscription'];
        }

        $packageFeature = DB::table('feature_saas_package')
            ->where('saas_package_id', $subscription->saas_package_id)
            ->where('feature_id', $feature->id)
            ->first();
        if (! $packageFeature) {
            return ['enabled' => false, 'limit' => null, 'source' => 'package'];
        }

        return ['enabled' => (bool) $packageFeature->enabled, 'limit' => $packageFeature->limit_value, 'source' => 'package'];
    }
}
