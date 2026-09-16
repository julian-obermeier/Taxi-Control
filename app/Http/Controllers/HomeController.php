<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->is_superadmin) {
            return redirect()->route('taxi-control.superadmin.dashboard');
        }

        $tenant = $user->tenants()->wherePivot('status', 'active')->orderBy('tenants.name')->first();
        abort_unless($tenant, 403, 'Dem Benutzer ist kein aktiver Mandant zugeordnet.');

        return redirect()->route('taxi-control.tenant.dashboard', ['tenant' => $tenant->slug]);
    }
}
