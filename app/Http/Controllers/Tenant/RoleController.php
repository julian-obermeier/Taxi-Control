<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditService;
use App\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(private readonly TenantContext $context, private readonly AuditService $audit)
    {
    }

    public function index(): View
    {
        return view('tenant.roles.index', [
            'tenant' => $this->context->requireTenant(),
            'roles' => Role::query()->withCount('permissions')->orderByDesc('is_system')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->formView(new Role(), []);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->context->requireTenant();
        $data = $this->validateRole($request);
        $permissionIds = array_values(array_unique($data['permissions'] ?? []));
        unset($data['permissions']);
        $data['slug'] = Str::slug($data['slug'] ?: $data['name']);
        $data['is_system'] = false;

        $role = DB::transaction(function () use ($data, $permissionIds, $tenant): Role {
            $role = Role::query()->create($data);
            $this->syncPermissions($tenant->id, $role->id, $permissionIds);
            return $role;
        });

        $this->audit->log('tenant.role.created', $role, [], ['name' => $role->name, 'permissions' => $permissionIds]);
        return redirect()->route('taxi-control.tenant.roles.edit', ['tenant' => $tenant->slug, 'role' => $role->id])->with('status', 'Rolle wurde angelegt.');
    }

    public function edit(int $role): View
    {
        $item = Role::query()->findOrFail($role);
        $selected = DB::table('role_permission')->where('tenant_id', $this->context->requireId())->where('role_id', $item->id)->pluck('permission_id')->all();
        return $this->formView($item, $selected);
    }

    public function update(Request $request, int $role): RedirectResponse
    {
        $tenant = $this->context->requireTenant();
        $item = Role::query()->findOrFail($role);
        $data = $this->validateRole($request, $item);
        $permissionIds = array_values(array_unique($data['permissions'] ?? []));
        unset($data['permissions']);

        if ($item->is_system) {
            unset($data['name'], $data['slug']);
        } else {
            $data['slug'] = Str::slug($data['slug'] ?: $data['name']);
        }

        DB::transaction(function () use ($item, $data, $permissionIds, $tenant): void {
            $item->update($data);
            $this->syncPermissions($tenant->id, $item->id, $permissionIds);
        });

        $this->audit->log('tenant.role.updated', $item, [], ['permissions' => $permissionIds]);
        return back()->with('status', 'Rolle und Berechtigungen wurden gespeichert.');
    }

    public function destroy(int $role): RedirectResponse
    {
        $tenant = $this->context->requireTenant();
        $item = Role::query()->findOrFail($role);
        abort_if($item->is_system, 422, 'Systemrollen können nicht gelöscht werden.');
        $item->delete();
        $this->audit->log('tenant.role.deleted', null, ['role_id' => $item->id, 'name' => $item->name], []);
        return redirect()->route('taxi-control.tenant.roles.index', ['tenant' => $tenant->slug])->with('status', 'Rolle wurde gelöscht.');
    }

    private function formView(Role $role, array $selected): View
    {
        $permissions = Permission::query()->orderBy('module')->orderBy('action')->get()->groupBy('module');
        return view('tenant.roles.form', [
            'tenant' => $this->context->requireTenant(),
            'role' => $role,
            'permissionsByModule' => $permissions,
            'selectedPermissions' => $selected,
        ]);
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        $tenantId = $this->context->requireId();
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', Rule::unique('roles', 'slug')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($role?->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);
    }

    private function syncPermissions(int $tenantId, int $roleId, array $permissionIds): void
    {
        DB::table('role_permission')->where('tenant_id', $tenantId)->where('role_id', $roleId)->delete();
        if ($permissionIds === []) {
            return;
        }
        DB::table('role_permission')->insert(array_map(fn (int $permissionId) => [
            'tenant_id' => $tenantId,
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'created_at' => now(),
            'updated_at' => now(),
        ], $permissionIds));
    }
}
