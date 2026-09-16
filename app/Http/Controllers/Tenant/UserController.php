<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly TenantContext $context, private readonly AuditService $audit)
    {
    }

    public function index(): View
    {
        $tenant = $this->context->requireTenant();
        $users = $tenant->users()->orderBy('users.name')->paginate(25);
        return view('tenant.users.index', compact('tenant', 'users'));
    }

    public function create(): View
    {
        return view('tenant.users.form', [
            'tenant' => $this->context->requireTenant(),
            'member' => null,
            'roles' => Role::query()->orderBy('name')->get(),
            'selectedRoles' => [],
            'permissionsByModule' => Permission::query()->orderBy('module')->orderBy('action')->get()->groupBy('module'),
            'permissionOverrides' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->context->requireTenant();
        $data = $this->validateMember($request, false);
        $roleIds = array_values(array_unique($data['roles'] ?? []));
        $overrides = $this->normalizedOverrides($data['permission_overrides'] ?? []);
        abort_unless(Role::query()->whereKey($roleIds)->count() === count($roleIds), 422, 'Mindestens eine Rolle gehört nicht zu diesem Mandanten.');

        $user = DB::transaction(function () use ($data, $roleIds, $overrides, $tenant): User {
            $user = User::query()->where('email', Str::lower($data['email']))->first();
            if (! $user) {
                abort_if(empty($data['password']), 422, 'Für ein neues Benutzerkonto ist ein Passwort erforderlich.');
                $user = User::query()->create(['name' => $data['name'], 'email' => Str::lower($data['email']), 'password' => $data['password']]);
            }
            DB::table('tenant_user')->updateOrInsert(
                ['tenant_id' => $tenant->id, 'user_id' => $user->id],
                ['status' => 'active', 'is_owner' => (bool) ($data['is_owner'] ?? false), 'updated_at' => now(), 'created_at' => now()]
            );
            $this->syncRoles($tenant->id, $user->id, $roleIds);
            $this->syncOverrides($tenant->id, $user->id, $overrides);
            return $user;
        });

        $this->audit->log('tenant.user.added', $user, [], ['user_id' => $user->id, 'roles' => $roleIds, 'overrides' => $overrides, 'is_owner' => (bool) ($data['is_owner'] ?? false)]);
        return redirect()->route('taxi-control.tenant.users.index', ['tenant' => $tenant->slug])->with('status', 'Benutzer wurde dem Mandanten zugeordnet.');
    }

    public function edit(int $user): View
    {
        $tenant = $this->context->requireTenant();
        $member = $tenant->users()->where('users.id', $user)->firstOrFail();
        $selectedRoles = DB::table('role_user')->where('tenant_id', $tenant->id)->where('user_id', $member->id)->pluck('role_id')->all();
        $permissionOverrides = DB::table('user_permission_overrides')->where('tenant_id', $tenant->id)->where('user_id', $member->id)->pluck('effect', 'permission_id')->all();
        return view('tenant.users.form', [
            'tenant' => $tenant,
            'member' => $member,
            'roles' => Role::query()->orderBy('name')->get(),
            'selectedRoles' => $selectedRoles,
            'permissionsByModule' => Permission::query()->orderBy('module')->orderBy('action')->get()->groupBy('module'),
            'permissionOverrides' => $permissionOverrides,
        ]);
    }

    public function update(Request $request, int $user): RedirectResponse
    {
        $tenant = $this->context->requireTenant();
        $member = $tenant->users()->where('users.id', $user)->firstOrFail();
        $data = $this->validateMember($request, true);
        $roleIds = array_values(array_unique($data['roles'] ?? []));
        $overrides = $this->normalizedOverrides($data['permission_overrides'] ?? []);
        abort_unless(Role::query()->whereKey($roleIds)->count() === count($roleIds), 422, 'Mindestens eine Rolle gehört nicht zu diesem Mandanten.');

        $wasOwner = (bool) $member->pivot->is_owner;
        $newOwner = (bool) ($data['is_owner'] ?? false);
        if ($wasOwner && (! $newOwner || $data['status'] !== 'active')) {
            $otherOwners = DB::table('tenant_user')->where('tenant_id', $tenant->id)->where('is_owner', true)->where('status', 'active')->where('user_id', '!=', $member->id)->count();
            abort_if($otherOwners === 0, 422, 'Der letzte aktive Eigentümer des Mandanten kann nicht entfernt oder deaktiviert werden.');
        }

        DB::transaction(function () use ($tenant, $member, $data, $roleIds, $overrides, $newOwner): void {
            DB::table('tenant_user')->where('tenant_id', $tenant->id)->where('user_id', $member->id)->update(['status' => $data['status'], 'is_owner' => $newOwner, 'updated_at' => now()]);
            $this->syncRoles($tenant->id, $member->id, $roleIds);
            $this->syncOverrides($tenant->id, $member->id, $overrides);
        });

        $this->audit->log('tenant.user.updated', $member, [], ['status' => $data['status'], 'roles' => $roleIds, 'overrides' => $overrides, 'is_owner' => $newOwner]);
        return back()->with('status', 'Benutzerzuordnung, Rollen und Einzelrechte wurden gespeichert.');
    }

    public function destroy(int $user): RedirectResponse
    {
        $tenant = $this->context->requireTenant();
        $member = $tenant->users()->where('users.id', $user)->firstOrFail();
        if ((bool) $member->pivot->is_owner) {
            $otherOwners = DB::table('tenant_user')->where('tenant_id', $tenant->id)->where('is_owner', true)->where('status', 'active')->where('user_id', '!=', $member->id)->count();
            abort_if($otherOwners === 0, 422, 'Der letzte aktive Eigentümer des Mandanten kann nicht entfernt werden.');
        }
        DB::transaction(function () use ($tenant, $member): void {
            DB::table('role_user')->where('tenant_id', $tenant->id)->where('user_id', $member->id)->delete();
            DB::table('user_permission_overrides')->where('tenant_id', $tenant->id)->where('user_id', $member->id)->delete();
            DB::table('tenant_user')->where('tenant_id', $tenant->id)->where('user_id', $member->id)->delete();
        });
        $this->audit->log('tenant.user.removed', $member, ['user_id' => $member->id], []);
        return redirect()->route('taxi-control.tenant.users.index', ['tenant' => $tenant->slug])->with('status', 'Benutzer wurde aus diesem Mandanten entfernt. Das globale Konto bleibt erhalten.');
    }

    private function validateMember(Request $request, bool $editing): array
    {
        $rules = [
            'roles' => ['array'], 'roles.*' => ['integer'], 'is_owner' => ['nullable', 'boolean'],
            'permission_overrides' => ['array'], 'permission_overrides.*' => [Rule::in(['inherit', 'allow', 'deny'])],
        ];
        if ($editing) {
            $rules['status'] = ['required', Rule::in(['active', 'inactive'])];
        } else {
            $rules['name'] = ['required', 'string', 'max:120'];
            $rules['email'] = ['required', 'email', 'max:255'];
            $rules['password'] = ['nullable', 'string', 'min:12'];
        }
        return $request->validate($rules);
    }

    private function normalizedOverrides(array $input): array
    {
        $validPermissionIds = Permission::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $validLookup = array_fill_keys($validPermissionIds, true);
        $result = [];
        foreach ($input as $permissionId => $effect) {
            $permissionId = (int) $permissionId;
            abort_unless(isset($validLookup[$permissionId]), 422, 'Unbekannte Berechtigung.');
            if (in_array($effect, ['allow', 'deny'], true)) $result[$permissionId] = $effect;
        }
        return $result;
    }

    private function syncRoles(int $tenantId, int $userId, array $roleIds): void
    {
        DB::table('role_user')->where('tenant_id', $tenantId)->where('user_id', $userId)->delete();
        if ($roleIds === []) return;
        DB::table('role_user')->insert(array_map(fn (int $roleId) => ['tenant_id' => $tenantId, 'role_id' => $roleId, 'user_id' => $userId, 'created_at' => now(), 'updated_at' => now()], $roleIds));
    }

    private function syncOverrides(int $tenantId, int $userId, array $overrides): void
    {
        DB::table('user_permission_overrides')->where('tenant_id', $tenantId)->where('user_id', $userId)->delete();
        if ($overrides === []) return;
        DB::table('user_permission_overrides')->insert(collect($overrides)->map(fn (string $effect, int $permissionId) => [
            'tenant_id' => $tenantId, 'user_id' => $userId, 'permission_id' => $permissionId, 'effect' => $effect, 'created_at' => now(), 'updated_at' => now(),
        ])->values()->all());
    }
}
