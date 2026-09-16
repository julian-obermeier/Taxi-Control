@php($editing = $driver !== null)
@extends('layouts.app', ['title' => $editing ? 'Fahrer bearbeiten' : 'Fahrer anlegen', 'heading' => $editing ? 'Fahrer bearbeiten' : 'Fahrer anlegen', 'eyebrow' => $tenant->name, 'tenant' => $tenant])
@section('content')
<form method="post" action="{{ $editing ? route('taxi-control.tenant.drivers.update',['tenant'=>$tenant->slug,'driverId'=>$driver->id]) : route('taxi-control.tenant.drivers.store',['tenant'=>$tenant->slug]) }}">
@csrf @if($editing) @method('PUT') @endif
<section class="panel">
    <div class="grid two">
        <div>
            <label class="field"><span>Personalnummer *</span><input name="employee_number" value="{{ old('employee_number',$driver?->employee_number) }}" required></label>
            <label class="field"><span>Anzeigename *</span><input name="display_name" value="{{ old('display_name',$driver?->display_name) }}" required></label>
            <label class="field"><span>Telefon</span><input name="phone" value="{{ old('phone',$driver?->phone) }}"></label>
            <label class="field"><span>Benutzerkonto</span><select name="user_id"><option value="">– nicht verknüpft –</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((string)old('user_id',$driver?->user_id)===(string)$user->id)>{{ $user->name }} · {{ $user->email }}</option>@endforeach</select></label>
        </div>
        <div>
            <label class="field"><span>Status</span><select name="status" required>@foreach(['offline'=>'Offline','available'=>'Verfügbar','ready'=>'Bereit','busy'=>'Besetzt','break'=>'Pause','unavailable'=>'Nicht verfügbar'] as $value=>$label)<option value="{{ $value }}" @selected(old('status',$driver?->status ?? 'offline')===$value)>{{ $label }}</option>@endforeach</select></label>
            <label class="field"><span>Qualifikationen</span><textarea rows="4" name="qualifications" placeholder="z. B. Rollstuhl, Krankenfahrt, Englisch">{{ old('qualifications',implode(', ', $driver?->qualifications ?? [])) }}</textarea><small>Mehrere Einträge durch Komma trennen.</small></label>
            <label class="check"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked((bool)old('is_active',$driver?->is_active ?? true))> Fahrer aktiv</label>
        </div>
    </div>
</section>
<div class="form-actions top-gap"><a class="btn" href="{{ route('taxi-control.tenant.drivers.index',['tenant'=>$tenant->slug]) }}">Abbrechen</a><div class="form-inline">@if($editing)<button class="btn btn-danger" type="submit" form="delete-driver">Löschen</button>@endif<button class="btn btn-primary" type="submit">Speichern</button></div></div>
</form>
@if($editing)<form id="delete-driver" method="post" action="{{ route('taxi-control.tenant.drivers.destroy',['tenant'=>$tenant->slug,'driverId'=>$driver->id]) }}" onsubmit="return confirm('Fahrer wirklich löschen?')">@csrf @method('DELETE')</form>@endif
@endsection
