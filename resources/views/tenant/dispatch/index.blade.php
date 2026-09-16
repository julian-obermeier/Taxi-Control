@extends('layouts.app', ['title' => 'Disposition', 'heading' => 'Leitstelle & Disposition', 'eyebrow' => $tenant->name, 'tenant' => $tenant])
@section('content')
<div class="toolbar">
    <div><p class="muted">Offene Fahrten, verfügbare Fahrer und Fahrzeuge in einer operativen Leitstellenansicht.</p></div>
    <div class="form-inline">
        <a class="btn" href="{{ route('taxi-control.tenant.trips.index', ['tenant' => $tenant->slug]) }}">Alle Fahrten</a>
        <a class="btn btn-primary" href="{{ route('taxi-control.tenant.trips.create', ['tenant' => $tenant->slug]) }}">Schnellauftrag</a>
    </div>
</div>

<div class="metrics top-gap">
    <div class="metric"><span>Offene Fahrten</span><strong>{{ $metrics['open'] }}</strong><small>nicht abgeschlossen</small></div>
    <div class="metric"><span>Ohne Fahrer</span><strong>{{ $metrics['unassigned'] }}</strong><small>Disposition erforderlich</small></div>
    <div class="metric"><span>Fahrer bereit</span><strong>{{ $metrics['driversReady'] }}</strong><small>verfügbar / bereit</small></div>
    <div class="metric"><span>Fahrzeuge frei</span><strong>{{ $metrics['vehiclesReady'] }}</strong><small>Status verfügbar</small></div>
</div>

<section class="panel top-gap">
    <div class="panel-header"><div><h2>Offene Aufträge</h2><p>Nach Priorität und geplantem Zeitpunkt sortiert.</p></div></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Auftrag</th><th>Status</th><th>Zeit</th><th>Abholung → Ziel</th><th>Fahrer</th><th>Fahrzeug</th><th></th></tr></thead>
        <tbody>
        @forelse($trips as $trip)
            <tr>
                <td><strong>{{ $trip->order_number }}</strong><br><small>{{ $trip->type?->name ?? 'Standard' }} · Prio {{ $trip->priority }}</small></td>
                <td><span class="status-pill warning">{{ $trip->status->label() }}</span></td>
                <td>{{ ($trip->scheduled_at ?? $trip->requested_at)?->format('d.m.Y H:i') ?? 'sofort' }}</td>
                <td><strong>{{ $trip->pickup_address }}</strong><br><small>{{ $trip->destination_address ?: 'Ziel offen' }}</small></td>
                <td>{{ $trip->driver?->display_name ?? '–' }}</td>
                <td>{{ $trip->vehicle?->fleet_number ?? '–' }}</td>
                <td class="actions"><a class="btn btn-small" href="{{ route('taxi-control.tenant.trips.show', ['tenant'=>$tenant->slug,'tripId'=>$trip->id]) }}">Öffnen</a></td>
            </tr>
        @empty
            <tr><td colspan="7"><div class="empty-state"><strong>Keine offenen Fahrten.</strong><span>Neue Aufträge erscheinen hier sofort.</span></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>

<div class="grid two top-gap">
    <section class="panel"><div class="panel-header"><div><h2>Fahrerstatus</h2><p>Aktive Fahrer des Mandanten.</p></div><a class="btn btn-small" href="{{ route('taxi-control.tenant.drivers.index',['tenant'=>$tenant->slug]) }}">Verwalten</a></div>
        <div class="table-wrap"><table><thead><tr><th>Fahrer</th><th>Status</th></tr></thead><tbody>
        @forelse($drivers as $driver)<tr><td><strong>{{ $driver->display_name }}</strong><br><small>{{ $driver->employee_number }}</small></td><td><span class="status-pill {{ in_array($driver->status,['available','ready']) ? 'success' : 'warning' }}">{{ ucfirst($driver->status) }}</span></td></tr>@empty<tr><td colspan="2">Keine Fahrer angelegt.</td></tr>@endforelse
        </tbody></table></div>
    </section>
    <section class="panel"><div class="panel-header"><div><h2>Fahrzeugstatus</h2><p>Aktive Fahrzeuge des Mandanten.</p></div><a class="btn btn-small" href="{{ route('taxi-control.tenant.vehicles.index',['tenant'=>$tenant->slug]) }}">Verwalten</a></div>
        <div class="table-wrap"><table><thead><tr><th>Fahrzeug</th><th>Status</th></tr></thead><tbody>
        @forelse($vehicles as $vehicle)<tr><td><strong>{{ $vehicle->fleet_number }}</strong><br><small>{{ $vehicle->license_plate }}</small></td><td><span class="status-pill {{ $vehicle->status === 'available' ? 'success' : 'warning' }}">{{ ucfirst($vehicle->status) }}</span></td></tr>@empty<tr><td colspan="2">Keine Fahrzeuge angelegt.</td></tr>@endforelse
        </tbody></table></div>
    </section>
</div>
@endsection
