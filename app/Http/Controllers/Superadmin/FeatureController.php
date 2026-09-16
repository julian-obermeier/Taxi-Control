<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FeatureController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function index(): View
    {
        return view('superadmin.features.index', ['features' => Feature::query()->withCount('packages')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('superadmin.features.form', ['feature' => new Feature(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $feature = Feature::query()->create($this->validated($request));
        $this->audit->log('superadmin.feature.created', $feature, [], $feature->toArray());
        return redirect()->route('taxi-control.superadmin.features.edit', $feature)->with('status', 'Feature wurde angelegt.');
    }

    public function edit(Feature $feature): View
    {
        return view('superadmin.features.form', compact('feature'));
    }

    public function update(Request $request, Feature $feature): RedirectResponse
    {
        $before = $feature->toArray();
        $feature->update($this->validated($request, $feature));
        $this->audit->log('superadmin.feature.updated', $feature, $before, $feature->fresh()->toArray());
        return back()->with('status', 'Feature wurde gespeichert.');
    }

    public function destroy(Feature $feature): RedirectResponse
    {
        if ($feature->packages()->exists()) {
            return back()->withErrors(['feature' => 'Das Feature ist mindestens einem Paket zugeordnet und kann nicht gelöscht werden.']);
        }
        $feature->delete();
        return redirect()->route('taxi-control.superadmin.features.index')->with('status', 'Feature wurde gelöscht.');
    }

    private function validated(Request $request, ?Feature $feature = null): array
    {
        return $request->validate([
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/', Rule::unique('features', 'key')->ignore($feature?->id)],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
