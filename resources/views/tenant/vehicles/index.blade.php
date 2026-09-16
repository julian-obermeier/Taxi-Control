@extends('layouts.app', ['title' => 'Fahrzeuge', 'heading' => 'Fahrzeugverwaltung', 'eyebrow' => $tenant->name, 'tenant' => $tenant])
@section('content')
<div class="toolbar"><div><p class="muted">Fuhrpark, Fahrzeugklassen, Sitzplätze und Ausstattungsmerkmale.</p></div><a class="btn btn-primary" href="{{ route('taxi-control.tenant.vehicles.create',['tenant'=>$tenant->slug]) }}">Fahrzeug anlegen</a></div>
<section class="panel top-gap"><div class="table-wrap"><table>
<thead><tr><th>Flottennr.</th><th>Kennzeichen</th><th>Klasse</th><th>Sitze</th><th>Status</th><th>Ausstattung</th><th></th></tr></thead>
<tbody>
@forelse($vehicles as $vehicle)
<tr>
<td><strong>{{ $vehicle->fleet_number }}</strong></td>
<td>{{ $vehicle->license_plate }}</td>
<td>{{ $vehicle->vehicle_class }}</td>
<td>{{ $vehicle->seats }}</td>
<td><span class="status-pill {{ $vehicle->status === 'available' ? 'success' : 'warning' }}">{{ ucfirst($vehicle->status) }}</span>@unless($vehicle->is_active)<br><small>deaktiviert</small>@endunless</td>
<td>{{ implode(', ', $vehicle->equipment ?? []) ?: '–' }}</td>
<td class="actions"><a class="btn btn-small" href="{{ route('taxi-control.tenant.vehicles.edit',['tenant'=>$tenant->slug,'vehicleId'=>$vehicle->id]) }}">Bearbeiten</a></td>
</tr>
@empty<tr><td colspan="7"><div class="empty-state"><strong>Noch keine Fahrzeuge angelegt.</strong></div></td></tr>@endforelse
</tbody></table></div><div class="pagination">{{ $vehicles->links() }}</div></section>
@endsection
