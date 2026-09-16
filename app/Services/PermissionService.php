<?php

namespace App\Services;

use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

final class PermissionService
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function allows(User $user, string $permissionKey): bool
    {
        if ($user->is_superadmin) {
            return true;
        }

        $tenantId = $this->context->requireId();

        $override = DB::table('user_permission_overrides as upo')
            ->join('permissions as p', 'p.id', '=', 'upo.permission_id')
            ->where('upo.tenant_id', $tenantId)
            ->where('upo.user_id', $user->id)
            ->where('p.key', $permissionKey)
            ->value('upo.effect');

        if ($override === 'deny') {
            return false;
        }
        if ($override === 'allow') {
            return true;
        }

        return DB::table('role_user as ru')
            ->join('role_permission as rp', function ($join): void {
                $join->on('rp.role_id', '=', 'ru.role_id')->on('rp.tenant_id', '=', 'ru.tenant_id');
            })
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('ru.tenant_id', $tenantId)
            ->where('ru.user_id', $user->id)
            ->where('p.key', $permissionKey)
            ->exists();
    }
}
