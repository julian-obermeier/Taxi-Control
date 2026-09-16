<?php

namespace App\Tenancy;

use App\Models\Tenant;
use Closure;
use RuntimeException;

final class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function clear(): void
    {
        $this->tenant = null;
    }

    public function has(): bool
    {
        return $this->tenant !== null;
    }

    public function tenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->getKey();
    }

    public function requireTenant(): Tenant
    {
        return $this->tenant ?? throw new RuntimeException('Kein aktiver Mandant im aktuellen Request-Kontext.');
    }

    public function requireId(): int
    {
        return (int) $this->requireTenant()->getKey();
    }

    public function run(Tenant $tenant, Closure $callback): mixed
    {
        $previous = $this->tenant;
        $this->tenant = $tenant;

        try {
            return $callback();
        } finally {
            $this->tenant = $previous;
        }
    }
}
