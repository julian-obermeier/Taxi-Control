<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\DataSubjectRequest;
use App\Models\Tenant;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PrivacyController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function index(Tenant $tenant): View
    {
        return view('tenant.privacy.index', ['tenant' => $tenant, 'requests' => DataSubjectRequest::query()->latest()->paginate(30)]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'request_type' => ['required', Rule::in(['access', 'rectification', 'erasure', 'restriction', 'portability', 'objection'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'due_at' => ['nullable', 'date'],
        ]);
        $item = DataSubjectRequest::query()->create([...$data, 'status' => 'open']);
        $this->audit->log('tenant.privacy_request.created', $item, [], $item->toArray(), [], $tenant->id);
        return back()->with('status', 'Datenschutzvorgang wurde angelegt.');
    }

    public function update(Request $request, Tenant $tenant, DataSubjectRequest $privacyRequest): RedirectResponse
    {
        abort_unless($privacyRequest->tenant_id === $tenant->id, 404);
        $data = $request->validate([
            'status' => ['required', Rule::in(['open', 'in_progress', 'completed', 'rejected'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'due_at' => ['nullable', 'date'],
        ]);
        $data['completed_at'] = $data['status'] === 'completed' ? now() : null;
        $privacyRequest->update($data);
        return back()->with('status', 'Datenschutzvorgang wurde aktualisiert.');
    }
}
