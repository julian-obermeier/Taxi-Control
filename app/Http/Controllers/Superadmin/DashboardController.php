<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SaasPackage;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('superadmin.dashboard', [
            'metrics' => [
                'tenants' => Tenant::query()->count(),
                'activeTenants' => Tenant::query()->where('status', 'active')->count(),
                'users' => User::query()->count(),
                'packages' => SaasPackage::query()->where('is_active', true)->count(),
                'subscriptions' => Subscription::query()->withoutGlobalScopes()->whereIn('status', ['trial', 'active'])->count(),
            ],
            'recentTenants' => Tenant::query()->latest()->limit(8)->get(),
        ]);
    }
}
