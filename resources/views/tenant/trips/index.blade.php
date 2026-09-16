@extends('layouts.app', ['title' => 'Fahrten', 'heading' => 'Fahrtenverwaltung', 'eyebrow' => $tenant->name, 'tenant' => $tenant])
@section('content')
<div class="toolbar">
    <form class="search-form" method="get">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Auftrag, Fahrgast, Adresse …">
        <select name="status">
            <option value="">Alle Status</option>
            @foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach
        </select>
        <button class="btn" type="submit">Filtern</button>
    </form>
    <a class="btn btn-primary" href="{{ route('taxi-control.tenant.trips.create',['tenant'=>$tenant->slug]) }}">Neue Fahrt</a>
</div>

<section class="panel top-gap"><div class="table-wrap"><table>
<thead><tr><th>Auftrag</th><th>Status</th><th>Termin</th><th>Fahrgast</th><th>Strecke</th><th>Fahrer / Fahrzeug</th><th></th></tr></thead>
<tbody>
@forelse($trips as $trip)
<tr>
    <td><strong>{{ $trip->order_number }}</strong><br><small>{{ $trip->type?->name ?? '–' }} · Prio {{ $trip->priority }}</small></td>
    <td><span class="status-pill {{ $trip->status->terminal() ? 'success' : 'warning' }}">{{ $trip->status->label() }}</span></td>
    <td>{{ ($trip->scheduled_at ?? $trip->requested_at)?->format('d.m.Y H:i') ?? '–' }}</td>
    <td>{{ $trip->passenger_name ?: '–' }}<br><small>{{ $trip->passenger_phone }}</small></td>
    <td><strong>{{ $trip->pickup_address }}</strong><br><small>{{ $trip->destination_address ?: 'Ziel offen' }}</small></td>
    <td>{{ $trip->driver?->display_name ?? '–' }}<br><small>{{ $trip->vehicle?->fleet_number ?? '–' }}</small></td>
    <td class="actions"><a class="btn btn-small" href="{{ route('taxi-control.tenant.trips.show',['tenant'=>$tenant->slug,'tripId'=>$trip->id]) }}">Öffnen</a></td>
</tr>
@empty<tr><td colspan="7"><div class="empty-state"><strong>Keine Fahrten gefunden.</strong></div></td></tr>@endforelse
</tbody></table></div><div class="pagination">{{ $trips->links() }}</div></section>
@endsection
