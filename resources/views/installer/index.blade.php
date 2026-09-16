@extends('layouts.app')
@section('content')
<div class="installer-wrap">
    <div class="installer-head">
        <div class="brand brand-large"><span class="brand-mark">TC</span><span><strong>Taxi-Control</strong><small>Webinstallation</small></span></div>
        <h1>System einrichten</h1>
        <p>Der Installer prüft den Server, richtet die gemeinsame Datenbank ein und erstellt den ersten Superadministrator.</p>
    </div>
    <div class="grid two installer-grid">
        <section class="panel">
            <div class="panel-header"><div><span class="eyebrow">Schritt 1</span><h2>Systemprüfung</h2></div></div>
            <div class="check-list">
                @foreach($checks as $label => $ok)
                    <div class="check-row"><span>{{ $label }}</span><span class="status-pill {{ $ok ? 'success' : 'danger' }}">{{ $ok ? 'OK' : 'Fehlt' }}</span></div>
                @endforeach
            </div>
        </section>
        <form class="panel" method="post" action="{{ route('taxi-control.install.store') }}">
            @csrf
            <div class="panel-header"><div><span class="eyebrow">Schritt 2</span><h2>Datenbank & Administrator</h2></div></div>
            <label class="field"><span>Öffentliche URL</span><input type="url" name="app_url" value="{{ old('app_url', request()->getSchemeAndHttpHost()) }}" required></label>
            <div class="form-row"><label class="field grow"><span>DB-Host</span><input name="db_host" value="{{ old('db_host', 'localhost') }}" required></label><label class="field small"><span>Port</span><input type="number" name="db_port" value="{{ old('db_port', 3306) }}" required></label></div>
            <label class="field"><span>Datenbank</span><input name="db_database" value="{{ old('db_database') }}" required></label>
            <label class="field"><span>DB-Benutzer</span><input name="db_username" value="{{ old('db_username') }}" required></label>
            <label class="field"><span>DB-Passwort</span><input type="password" name="db_password"></label>
            <hr>
            <label class="field"><span>Name Superadministrator</span><input name="admin_name" value="{{ old('admin_name') }}" required></label>
            <label class="field"><span>E-Mail Superadministrator</span><input type="email" name="admin_email" value="{{ old('admin_email') }}" required></label>
            <label class="field"><span>Passwort (mind. 12 Zeichen)</span><input type="password" name="admin_password" required></label>
            <label class="field"><span>Passwort bestätigen</span><input type="password" name="admin_password_confirmation" required></label>
            <button class="btn btn-primary btn-block" type="submit">Taxi-Control installieren</button>
        </form>
    </div>
</div>
@endsection
