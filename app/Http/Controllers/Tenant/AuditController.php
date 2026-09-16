<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        $query = AuditLog::query()->where('tenant_id', $tenant->id)->latest('id');
        if ($event = trim((string) $request->query('event'))) {
            $query->where('event', 'like', $event.'%');
        }
        return view('tenant.audit.index', ['tenant' => $tenant, 'logs' => $query->paginate(50)->withQueryString()]);
    }
}
