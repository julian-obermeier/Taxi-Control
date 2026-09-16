@extends('layouts.app', ['title' => 'Fahrer', 'heading' => 'Fahrerverwaltung', 'eyebrow' => $tenant->name, 'tenant' => $tenant])
@section('content')
<div class="toolbar"><div><p class="muted">Fahrerprofile, Verfügbarkeit und optionale Verknüpfung mit Benutzerkonten.</p></div><a class="btn btn-primary" href="{{ route('taxi-control.tenant.drivers.create',['tenant'=>$tenant->slug]) }}">Fahrer anlegen</a></div>
<section class="panel top-gap"><div class="table-wrap"><table>
<thead><tr><th>Personalnr.</th><th>Fahrer</th><th>Benutzer</th><th>Status</th><th>Qualifikationen</th><th></th></tr></thead>
<tbody>
@forelse($drivers as $driver)
<tr>
<td><strong>{{ $driver->employee_number }}</strong></td>
<td>{{ $driver->display_name }}<br><small>{{ $driver->phone ?: 'keine Telefonnummer' }}</small></td>
<td>{{ $driver->user?->email ?? 'nicht verknüpft' }}</td>
<td><span class="status-pill {{ in_array($driver->status,['available','ready']) ? 'success' : 'warning' }}">{{ ucfirst($driver->status) }}</span>@unless($driver->is_active)<br><small>deaktiviert</small>@endunless</td>
<td>{{ implode(', ', $driver->qualifications ?? []) ?: '–' }}</td>
<td class="actions"><a class="btn btn-small" href="{{ route('taxi-control.tenant.drivers.edit',['tenant'=>$tenant->slug,'driverId'=>$driver->id]) }}">Bearbeiten</a></td>
</tr>
@empty<tr><td colspan="6"><div class="empty-state"><strong>Noch keine Fahrer angelegt.</strong></div></td></tr>@endforelse
</tbody></table></div><div class="pagination">{{ $drivers->links() }}</div></section>
@endsection
