@extends('layouts.app', ['title' => $tenant->name, 'heading' => 'Unternehmensdashboard', 'eyebrow' => $tenant->name, 'tenant' => $tenant])
@section('content')
<div class="tenant-hero panel">
    <div><span class="eyebrow">Mandant</span><h2>{{ $tenant->name }}</h2><p>Mandantenpfad: <code>/taxi-control/{{ $tenant->slug }}</code></p></div>
    <span class="status-pill {{ $tenant->status === 'active' ? 'success' : 'warning' }}">{{ ucfirst($tenant->status) }}</span>
</div>
<div class="metrics top-gap">
    <div class="metric"><span>Benutzer</span><strong>{{ $metrics['users'] }}</strong><small>aktive Mitgliedschaften</small></div>
    <div class="metric"><span>Rollen</span><strong>{{ $metrics['roles'] }}</strong><small>mandantenspezifisch</small></div>
    <div class="metric"><span>Onboarding</span><strong>{{ $metrics['onboarding'] ? 'Ja' : 'Offen' }}</strong><small>Einrichtungsstatus</small></div>
    <div class="metric"><span>Go-Live</span><strong>{{ $metrics['goLive'] ? 'Aktiv' : 'Noch nicht' }}</strong><small>Freigabestatus</small></div>
</div>
<section class="panel top-gap">
    <div class="panel-header"><div><span class="eyebrow">Phase 1</span><h2>SaaS-Fundament</h2></div></div>
    <div class="feature-grid">
        <div class="feature"><strong>Tenant-Kontext</strong><span>Serverseitig auf {{ $tenant->id }} begrenzt</span></div>
        <div class="feature"><strong>Rollenmodell</strong><span>Mehrfachrollen und Einzelrechte vorbereitet</span></div>
        <div class="feature"><strong>Audit & API</strong><span>Grundtabellen und Versionierung angelegt</span></div>
        <div class="feature"><strong>Shared Hosting</strong><span>Keine dauerhafte Node-/Redis-Pflicht</span></div>
    </div>
</section>
@endsection
