<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Tenancy\TenantContext;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function __invoke(): View
    {
        $tenant = $this->context->requireTenant();

        return view('tenant.dashboard', [
            'tenant' => $tenant,
            'metrics' => [
                'users' => $tenant->users()->wherePivot('status', 'active')->count(),
                'roles' => Role::query()->count(),
                'goLive' => $tenant->go_live_at !== null,
                'onboarding' => $tenant->onboarding_completed_at !== null,
            ],
        ]);
    }
}
