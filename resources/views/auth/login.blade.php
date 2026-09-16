@extends('layouts.app')
@section('content')
<div class="auth-wrap">
    <div class="auth-hero">
        <div class="brand brand-large"><span class="brand-mark">TC</span><span><strong>Taxi-Control</strong><small>Professionelle Taxi-Dispatch-Plattform</small></span></div>
        <h2>Leitstelle, Fahrer und Verwaltung in einer Plattform.</h2>
        <p>Mandantenfähig, shared-hosting-tauglich und auf sichere Betriebsprozesse ausgelegt.</p>
        <div class="feature-grid compact">
            <div class="feature"><strong>Mandantentrennung</strong><span>Serverseitig über tenant_id</span></div>
            <div class="feature"><strong>Sicherer Zugang</strong><span>2FA und Audit-Grundlage</span></div>
            <div class="feature"><strong>Shared Hosting</strong><span>Kein dauerhafter Node-/Redis-Zwang</span></div>
        </div>
    </div>
    <form class="panel auth-panel" method="post" action="{{ route('taxi-control.login.store') }}">
        @csrf
        <div class="panel-header"><div><span class="eyebrow">Anmeldung</span><h1>Willkommen zurück</h1></div></div>
        <label class="field"><span>E-Mail-Adresse</span><input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus></label>
        <label class="field"><span>Passwort</span><input type="password" name="password" autocomplete="current-password" required></label>
        <label class="check"><input type="checkbox" name="remember" value="1"><span>Angemeldet bleiben</span></label>
        <button class="btn btn-primary btn-block" type="submit">Sicher anmelden</button>
    </form>
</div>
@endsection
