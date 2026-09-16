<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
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
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->context->requireTenant();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:12'],
            'roles' => ['array'],
            'roles.*' => ['integer'],
        ]);

        $roleIds = array_values(array_unique($data['roles'] ?? []));
        abort_unless(Role::query()->whereKey($roleIds)->count() === count($roleIds), 422, 'Mindestens eine Rolle gehört nicht zu diesem Mandanten.');

        $user = DB::transaction(function () use ($data, $roleIds, $tenant): User {
            $user = User::query()->where('email', Str::lower($data['email']))->first();
            if (! $user) {
                abort_if(empty($data['password']), 422, 'Für ein neues Benutzerkonto ist ein Passwort erforderlich.');
                $user = User::query()->create([
                    'name' => $data['name'],
                    'email' => Str::lower($data['email']),
                    'password' => $data['password'],
                ]);
            }

            DB::table('tenant_user')->updateOrInsert(
                ['tenant_id' => $tenant->id, 'user_id' => $user->id],
                ['status' => 'active', 'is_owner' => false, 'updated_at' => now(), 'created_at' => now()]
            );

            $this->syncRoles($tenant->id, $user->id, $roleIds);
            return $user;
        });

        $this->audit->log('tenant.user.added', $user, [], ['user_id' => $user->id, 'roles' => $roleIds]);

        return redirect()->route('taxi-control.tenant.users.index', ['tenant' => $tenant->slug])->with('status', 'Benutzer wurde dem Mandanten zugeordnet.');
    }

    public function edit(int $user): View
    {
        $tenant = $this->context->requireTenant();
        $member = $tenant->users()->where('users.id', $user)->firstOrFail();
        $selectedRoles = DB::table('role_user')->where('tenant_id', $tenant->id)->where('user_id', $member->id)->pluck('role_id')->all();

        return view('tenant.users.form', [
            'tenant' => $tenant,
            'member' => $member,
            'roles' => Role::query()->orderBy('name')->get(),
            'selectedRoles' => $selectedRoles,
        ]);
    }

    public function update(Request $request, int $user): RedirectResponse
    {
        $tenant = $this->context->requireTenant();
        $member = $tenant->users()->where('users.id', $user)->firstOrFail();
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'roles' => ['array'],
            'roles.*' => ['integer'],
        ]);
        $roleIds = array_values(array_unique($data['roles'] ?? []));
        abort_unless(Role::query()->whereKey($roleIds)->count() === count($roleIds), 422, 'Mindestens eine Rolle gehört nicht zu diesem Mandanten.');

        DB::transaction(function () use ($tenant, $member, $data, $roleIds): void {
            DB::table('tenant_user')->where('tenant_id', $tenant->id)->where('user_id', $member->id)->update(['status' => $data['status'], 'updated_at' => now()]);
            $this->syncRoles($tenant->id, $member->id, $roleIds);
        });

        $this->audit->log('tenant.user.updated', $member, [], ['status' => $data['status'], 'roles' => $roleIds]);
        return back()->with('status', 'Benutzerzuordnung wurde gespeichert.');
    }

    public function destroy(int $user): RedirectResponse
    {
        $tenant = $this->context->requireTenant();
        $member = $tenant->users()->where('users.id', $user)->firstOrFail();

        DB::transaction(function () use ($tenant, $member): void {
            DB::table('role_user')->where('tenant_id', $tenant->id)->where('user_id', $member->id)->delete();
            DB::table('user_permission_overrides')->where('tenant_id', $tenant->id)->where('user_id', $member->id)->delete();
            DB::table('tenant_user')->where('tenant_id', $tenant->id)->where('user_id', $member->id)->delete();
        });

        $this->audit->log('tenant.user.removed', $member, ['user_id' => $member->id], []);
        return redirect()->route('taxi-control.tenant.users.index', ['tenant' => $tenant->slug])->with('status', 'Benutzer wurde aus diesem Mandanten entfernt. Das globale Konto bleibt erhalten.');
    }

    private function syncRoles(int $tenantId, int $userId, array $roleIds): void
    {
        DB::table('role_user')->where('tenant_id', $tenantId)->where('user_id', $userId)->delete();
        if ($roleIds === []) {
            return;
        }
        DB::table('role_user')->insert(array_map(fn (int $roleId) => [
            'tenant_id' => $tenantId,
            'role_id' => $roleId,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ], $roleIds));
    }
}
