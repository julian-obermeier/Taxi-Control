@extends('layouts.app', ['title' => 'Fahrt '.$trip->order_number, 'heading' => 'Fahrt '.$trip->order_number, 'eyebrow' => $tenant->name, 'tenant' => $tenant])
@section('content')
<div class="toolbar">
    <div><span class="status-pill {{ $trip->status->terminal() ? 'success' : 'warning' }}">{{ $trip->status->label() }}</span></div>
    <div class="form-inline">
        <a class="btn" href="{{ route('taxi-control.tenant.trips.index',['tenant'=>$tenant->slug]) }}">Zurück</a>
        @unless($trip->status->terminal())<a class="btn" href="{{ route('taxi-control.tenant.trips.edit',['tenant'=>$tenant->slug,'tripId'=>$trip->id]) }}">Bearbeiten</a>@endunless
    </div>
</div>

<div class="grid two top-gap">
    <section class="panel">
        <div class="panel-header"><div><h2>Auftragsdaten</h2><p>{{ $trip->type?->name ?? 'Standardfahrt' }} · Priorität {{ $trip->priority }}</p></div></div>
        <div class="info-box"><strong>Abholung</strong><span>{{ $trip->pickup_address }}</span></div>
        <div class="info-box"><strong>Ziel</strong><span>{{ $trip->destination_address ?: 'Noch nicht angegeben' }}</span></div>
        <div class="grid two">
            <div class="info-box"><strong>Fahrgast</strong><span>{{ $trip->passenger_name ?: '–' }} · {{ $trip->passenger_count }} Pers.</span></div>
            <div class="info-box"><strong>Telefon</strong><span>{{ $trip->passenger_phone ?: '–' }}</span></div>
            <div class="info-box"><strong>Zeit</strong><span>{{ ($trip->scheduled_at ?? $trip->requested_at)?->format('d.m.Y H:i') ?? 'sofort' }}</span></div>
            <div class="info-box"><strong>Fahrzeugklasse</strong><span>{{ $trip->vehicle_class ?: 'beliebig' }}</span></div>
        </div>
        @if($trip->notes)<div class="info-box"><strong>Notizen</strong><span>{{ $trip->notes }}</span></div>@endif
    </section>

    <section class="panel">
        <div class="panel-header"><div><h2>Zuweisung</h2><p>Fahrer und Fahrzeug werden ausschließlich aus diesem Mandanten geladen.</p></div></div>
        <form method="post" action="{{ route('taxi-control.tenant.trips.assign',['tenant'=>$tenant->slug,'tripId'=>$trip->id]) }}">
            @csrf @method('PUT')
            <label class="field"><span>Fahrer</span><select name="driver_id"><option value="">– keine Zuweisung –</option>@foreach($drivers as $driver)<option value="{{ $driver->id }}" @selected((string)$trip->driver_id===(string)$driver->id)>{{ $driver->display_name }} · {{ $driver->status }}</option>@endforeach</select></label>
            <label class="field"><span>Fahrzeug</span><select name="vehicle_id"><option value="">– keine Zuweisung –</option>@foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}" @selected((string)$trip->vehicle_id===(string)$vehicle->id)>{{ $vehicle->fleet_number }} · {{ $vehicle->license_plate }} · {{ $vehicle->vehicle_class }}</option>@endforeach</select></label>
            <button class="btn btn-primary" type="submit">Zuweisung speichern</button>
        </form>
        <hr>
        <div class="info-box"><strong>Aktuell</strong><span>Fahrer: {{ $trip->driver?->display_name ?? '–' }} · Fahrzeug: {{ $trip->vehicle?->fleet_number ?? '–' }}</span></div>
    </section>
</div>

<div class="grid two top-gap">
    <section class="panel">
        <div class="panel-header"><div><h2>Statussteuerung</h2><p>Nur zulässige nächste Schritte werden angeboten.</p></div></div>
        @if(count($allowedStatuses))
        <form method="post" action="{{ route('taxi-control.tenant.trips.status',['tenant'=>$tenant->slug,'tripId'=>$trip->id]) }}">
            @csrf @method('PUT')
            <label class="field"><span>Nächster Status</span><select name="status" required>@foreach($allowedStatuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select></label>
            <label class="field"><span>Grund / Notiz</span><textarea rows="3" name="reason"></textarea></label>
            <button class="btn btn-primary" type="submit">Status ändern</button>
        </form>
        @else<div class="empty-state"><strong>Kein weiterer Statuswechsel.</strong><span>Die Fahrt befindet sich in einem Endstatus.</span></div>@endif
    </section>

    <section class="panel">
        <div class="panel-header"><div><h2>Zwischenstopps</h2><p>Vorhandene Stopps in geplanter Reihenfolge.</p></div></div>
        @forelse($trip->stops as $stop)<div class="info-box"><strong>{{ $stop->sequence }}. {{ $stop->address }}</strong><span>{{ $stop->contact_name ?: 'Kein Kontakt hinterlegt' }}</span></div>@empty<div class="empty-state"><strong>Keine Zwischenstopps.</strong></div>@endforelse
    </section>
</div>

<section class="panel top-gap">
    <div class="panel-header"><div><h2>Statushistorie</h2><p>Chronologisches Protokoll der Statuswechsel.</p></div></div>
    <div class="table-wrap"><table><thead><tr><th>Zeit</th><th>Von</th><th>Nach</th><th>Quelle</th><th>Grund</th></tr></thead><tbody>
    @forelse($trip->stateEvents as $event)<tr><td>{{ $event->created_at?->format('d.m.Y H:i:s') }}</td><td>{{ $event->from_status ?: '–' }}</td><td>{{ $event->to_status }}</td><td>{{ $event->source }}</td><td>{{ $event->reason ?: '–' }}</td></tr>@empty<tr><td colspan="5">Noch keine Statuswechsel protokolliert.</td></tr>@endforelse
    </tbody></table></div>
</section>
@endsection
