<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BrandingController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function edit(Tenant $tenant): View
    {
        return view('tenant.branding.edit', ['tenant' => $tenant, 'branding' => $tenant->branding ?? []]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'company_short_name' => ['nullable', 'string', 'max:60'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:4096'],
            'favicon' => ['nullable', 'image', 'mimes:png,ico,jpg,jpeg,webp', 'max:1024'],
        ]);

        $branding = $tenant->branding ?? [];
        $branding['primary_color'] = strtolower($data['primary_color']);
        $branding['accent_color'] = strtolower($data['accent_color']);
        $branding['company_short_name'] = $data['company_short_name'] ?? null;

        $directory = public_path('uploads/tenant-branding/'.$tenant->id);
        File::ensureDirectoryExists($directory);
        foreach (['logo', 'favicon'] as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $extension = strtolower($file->getClientOriginalExtension());
                $filename = $field.'-'.Str::random(12).'.'.$extension;
                $file->move($directory, $filename);
                if (! empty($branding[$field])) {
                    $old = public_path(ltrim((string) $branding[$field], '/'));
                    if (File::isFile($old) && str_starts_with(realpath($old) ?: '', realpath($directory) ?: $directory)) File::delete($old);
                }
                $branding[$field] = '/uploads/tenant-branding/'.$tenant->id.'/'.$filename;
            }
        }

        $before = $tenant->branding ?? [];
        $tenant->update(['branding' => $branding]);
        $this->audit->log('tenant.branding.updated', $tenant, $before, $branding, [], $tenant->id);
        return back()->with('status', 'Branding wurde gespeichert.');
    }
}
