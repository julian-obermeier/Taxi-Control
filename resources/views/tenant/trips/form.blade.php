@php($editing = $trip !== null)
@extends('layouts.app', ['title' => $editing ? 'Fahrt bearbeiten' : 'Neue Fahrt', 'heading' => $editing ? 'Fahrt bearbeiten' : 'Schnellauftrag anlegen', 'eyebrow' => $tenant->name, 'tenant' => $tenant])
@section('content')
<form method="post" action="{{ $editing ? route('taxi-control.tenant.trips.update',['tenant'=>$tenant->slug,'tripId'=>$trip->id]) : route('taxi-control.tenant.trips.store',['tenant'=>$tenant->slug]) }}">
@csrf
@if($editing) @method('PUT') @endif
<div class="grid two">
    <section class="panel">
        <div class="panel-header"><div><h2>Auftrag</h2><p>Grunddaten für die Disposition.</p></div></div>
        <label class="field"><span>Fahrtart</span><select name="trip_type_id"><option value="">Standard</option>@foreach($types as $type)<option value="{{ $type->id }}" @selected((string)old('trip_type_id',$trip?->trip_type_id)===(string)$type->id)>{{ $type->name }}</option>@endforeach</select></label>
        <div class="form-row">
            <label class="field grow"><span>Fahrgast</span><input name="passenger_name" value="{{ old('passenger_name',$trip?->passenger_name) }}"></label>
            <label class="field grow"><span>Telefon</span><input name="passenger_phone" value="{{ old('passenger_phone',$trip?->passenger_phone) }}"></label>
        </div>
        <div class="form-row">
            <label class="field grow"><span>Personen</span><input type="number" min="1" max="99" name="passenger_count" value="{{ old('passenger_count',$trip?->passenger_count ?? 1) }}" required></label>
            <label class="field grow"><span>Priorität 1–100</span><input type="number" min="1" max="100" name="priority" value="{{ old('priority',$trip?->priority ?? 50) }}"></label>
        </div>
        <label class="field"><span>Fahrzeugklasse</span><input name="vehicle_class" value="{{ old('vehicle_class',$trip?->vehicle_class) }}" placeholder="z. B. standard, grossraum, rollstuhl"></label>
        <label class="field"><span>Geplanter Zeitpunkt</span><input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at',$trip?->scheduled_at?->format('Y-m-d\TH:i')) }}"><small>Leer lassen für sofortige Disposition.</small></label>
    </section>

    <section class="panel">
        <div class="panel-header"><div><h2>Strecke</h2><p>Ziel kann bei einer offenen Taxifahrt zunächst leer bleiben.</p></div></div>
        <label class="field"><span>Abholadresse *</span><textarea rows="3" name="pickup_address" required>{{ old('pickup_address',$trip?->pickup_address) }}</textarea></label>
        <label class="field"><span>Zieladresse</span><textarea rows="3" name="destination_address">{{ old('destination_address',$trip?->destination_address) }}</textarea></label>
        <label class="field"><span>Notizen</span><textarea rows="5" name="notes">{{ old('notes',$trip?->notes) }}</textarea></label>
    </section>
</div>
<div class="form-actions top-gap">
    <a class="btn" href="{{ $editing ? route('taxi-control.tenant.trips.show',['tenant'=>$tenant->slug,'tripId'=>$trip->id]) : route('taxi-control.tenant.dispatch.index',['tenant'=>$tenant->slug]) }}">Abbrechen</a>
    <button class="btn btn-primary" type="submit">{{ $editing ? 'Änderungen speichern' : 'Fahrt anlegen' }}</button>
</div>
</form>
@endsection
