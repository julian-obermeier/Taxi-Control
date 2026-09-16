<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><meta name="color-scheme" content="light dark">
    <title>{{ $title ?? 'Taxi-Control' }}</title><link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body><div class="app-shell">
@auth
<aside class="sidebar" id="sidebar">
    <a class="brand" href="{{ route('taxi-control.home') }}"><span class="brand-mark">TC</span><span><strong>Taxi-Control</strong><small>Dispatch SaaS</small></span></a>
    <nav class="nav">
        @if(auth()->user()->is_superadmin)
            <a class="nav-link" href="{{ route('taxi-control.superadmin.dashboard') }}">SaaS-Control-Center</a>
            <a class="nav-link" href="{{ route('taxi-control.superadmin.tenants.index') }}">Mandanten</a>
        @endif
        @isset($tenant)<a class="nav-link" href="{{ route('taxi-control.tenant.dashboard', ['tenant' => $tenant->slug]) }}">Dashboard</a>@endisset
        <a class="nav-link" href="{{ route('taxi-control.account.2fa') }}">Sicherheit / 2FA</a>
    </nav>
    <div class="sidebar-footer"><div class="user-card"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->email }}</small></div><form method="post" action="{{ route('taxi-control.logout') }}">@csrf<button class="btn btn-ghost btn-block" type="submit">Abmelden</button></form></div>
</aside>
@endauth
<main class="main {{ auth()->check() ? '' : 'main-public' }}">
@auth<header class="topbar"><button class="icon-btn mobile-only" type="button" data-sidebar-toggle aria-label="Navigation öffnen">☰</button><div><div class="eyebrow">{{ $eyebrow ?? 'Taxi-Control' }}</div><h1>{{ $heading ?? ($title ?? 'Taxi-Control') }}</h1></div><div class="topbar-actions"><span class="status-pill success">System online</span></div></header>@endauth
<section class="content">@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif @if($errors->any())<div class="alert alert-danger"><strong>Bitte prüfen:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif @yield('content')</section>
</main></div><script src="{{ asset('assets/js/app.js') }}" defer></script></body></html>
