@extends('layouts.app', ['title' => 'Mandanten', 'heading' => 'Mandantenverwaltung', 'eyebrow' => 'SaaS-Control-Center'])
@section('content')
<div class="toolbar"><form method="get" class="search-form"><input name="q" value="{{ request('q') }}" placeholder="Name oder Slug suchen"><button class="btn" type="submit">Suchen</button></form><a class="btn btn-primary" href="{{ route('taxi-control.superadmin.tenants.create') }}">Mandant anlegen</a></div>
<section class="panel top-gap">
<div class="table-wrap"><table><thead><tr><th>Unternehmen</th><th>Mandantenpfad</th><th>Status</th><th>Onboarding</th><th>Go-Live</th><th></th></tr></thead><tbody>
@forelse($tenants as $item)<tr><td><strong>{{ $item->name }}</strong><br><small>{{ $item->legal_name ?: '–' }}</small></td><td><code>/taxi-control/{{ $item->slug }}</code></td><td><span class="status-pill {{ $item->status === 'active' ? 'success' : ($item->status === 'trial' ? 'warning' : 'danger') }}">{{ ucfirst($item->status) }}</span></td><td>{{ $item->onboarding_completed_at ? 'Abgeschlossen' : 'Offen' }}</td><td>{{ $item->go_live_at ? $item->go_live_at->format('d.m.Y') : '–' }}</td><td class="actions"><a class="btn btn-small" href="{{ route('taxi-control.superadmin.tenants.edit', $item) }}">Bearbeiten</a><a class="btn btn-small" href="{{ route('taxi-control.tenant.dashboard', ['tenant' => $item->slug]) }}">Öffnen</a></td></tr>@empty<tr><td colspan="6"><div class="empty-state"><strong>Noch keine Mandanten vorhanden.</strong><span>Lege das erste Taxiunternehmen an.</span></div></td></tr>@endforelse
</tbody></table></div><div class="pagination">{{ $tenants->links() }}</div>
</section>
@endsection
