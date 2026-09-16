<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class AuditService
{
    public function __construct(private readonly TenantContext $context, private readonly Request $request)
    {
    }

    public function log(string $event, ?Model $subject = null, array $old = [], array $new = [], array $metadata = [], ?int $tenantId = null): void
    {
        AuditLog::query()->create([
            'tenant_id' => $tenantId ?? $this->context->id(),
            'user_id' => $this->request->user()?->getKey(),
            'event' => $event,
            'auditable_type' => $subject ? $subject::class : null,
            'auditable_id' => $subject?->getKey(),
            'ip_address' => $this->request->ip(),
            'user_agent' => substr((string) $this->request->userAgent(), 0, 1000),
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'metadata' => $metadata ?: null,
            'created_at' => now(),
        ]);
    }
}
