<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model): void {
            $tenantId = app(TenantContext::class)->requireId();

            if ($model->tenant_id !== null && (int) $model->tenant_id !== $tenantId) {
                throw new LogicException('Mandantenwechsel beim Erstellen eines Datensatzes wurde blockiert.');
            }

            $model->tenant_id = $tenantId;
        });

        static::updating(function ($model): void {
            if ($model->isDirty('tenant_id')) {
                throw new LogicException('tenant_id darf nicht verändert werden.');
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
