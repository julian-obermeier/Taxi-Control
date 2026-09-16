@extends('layouts.app', ['title' => 'Sicherheit', 'heading' => 'Zwei-Faktor-Authentifizierung', 'eyebrow' => 'Konto'])
@section('content')
<div class="grid two">
    <section class="panel">
        <div class="panel-header"><div><span class="eyebrow">Status</span><h2>{{ auth()->user()->two_factor_enabled_at ? '2FA ist aktiv' : '2FA aktivieren' }}</h2></div><span class="status-pill {{ auth()->user()->two_factor_enabled_at ? 'success' : 'warning' }}">{{ auth()->user()->two_factor_enabled_at ? 'Geschützt' : 'Empfohlen' }}</span></div>
        @if(!auth()->user()->two_factor_enabled_at)
            <p>Fügen Sie Taxi-Control in Ihrer Authenticator-App über den folgenden Schlüssel oder die Provisioning-URI hinzu. Der Schlüssel wird erst nach erfolgreicher Code-Prüfung dauerhaft gespeichert.</p>
            <div class="secret-box"><small>Secret</small><code>{{ $secret }}</code></div>
            <details><summary>Provisioning-URI anzeigen</summary><code class="break">{{ $provisioningUri }}</code></details>
            <form method="post" action="{{ route('taxi-control.account.2fa.enable') }}" class="form-inline top-gap">@csrf<label class="field grow"><span>Aktueller 6-stelliger Code</span><input type="text" inputmode="numeric" maxlength="6" name="code" required></label><button class="btn btn-primary" type="submit">2FA aktivieren</button></form>
        @else
            <p>Ihr Konto verlangt nach dem Passwort zusätzlich einen zeitbasierten Einmalcode.</p>
        @endif
    </section>

    @if(auth()->user()->two_factor_enabled_at)
    <section class="panel danger-zone">
        <div class="panel-header"><div><span class="eyebrow">Sicherheitsänderung</span><h2>2FA deaktivieren</h2></div></div>
        <p>Zum Schutz des Kontos werden Passwort und ein gültiger aktueller Sicherheitscode verlangt.</p>
        <form method="post" action="{{ route('taxi-control.account.2fa.disable') }}">@csrf @method('DELETE')
            <label class="field"><span>Passwort</span><input type="password" name="password" required></label>
            <label class="field"><span>Sicherheitscode</span><input type="text" inputmode="numeric" maxlength="6" name="code" required></label>
            <button class="btn btn-danger" type="submit">2FA deaktivieren</button>
        </form>
    </section>
    @endif
</div>
@endsection
