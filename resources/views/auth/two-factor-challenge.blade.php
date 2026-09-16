@extends('layouts.app')
@section('content')
<div class="auth-wrap single">
    <form class="panel auth-panel" method="post" action="{{ route('taxi-control.2fa.verify') }}">
        @csrf
        <div class="panel-header"><div><span class="eyebrow">Zwei-Faktor-Authentifizierung</span><h1>Sicherheitscode</h1><p>Geben Sie den sechsstelligen Code aus Ihrer Authenticator-App ein.</p></div></div>
        <label class="field"><span>6-stelliger Code</span><input class="otp" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" name="code" autocomplete="one-time-code" required autofocus></label>
        <button class="btn btn-primary btn-block" type="submit">Code prüfen</button>
    </form>
</div>
@endsection
