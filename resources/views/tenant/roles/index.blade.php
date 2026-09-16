@extends('layouts.app', ['title' => 'Rollen', 'heading' => 'Rollen & Rechte', 'eyebrow' => $tenant->name, 'tenant' => $tenant])
@section('content')
<div class="toolbar"><p class="muted">Mehrere Rollen pro Benutzer werden unterstützt. Einzelrechte können zusätzlich separat übersteuert werden.</p><a class="btn btn-primary" href="{{ route('taxi-control.tenant.roles.create',['tenant'=>$tenant->slug]) }}">Eigene Rolle anlegen</a></div>
<div class="card-grid top-gap">@foreach($roles as $role)<a class="panel card-link" href="{{ route('taxi-control.tenant.roles.edit',['tenant'=>$tenant->slug,'role'=>$role->id]) }}"><div class="panel-header"><div><span class="eyebrow">{{ $role->is_system ? 'Systemrolle' : 'Eigene Rolle' }}</span><h2>{{ $role->name }}</h2></div><span class="status-pill success">{{ $role->permissions_count }} Rechte</span></div><p>{{ $role->description ?: 'Keine Beschreibung' }}</p></a>@endforeach</div>
@endsection
