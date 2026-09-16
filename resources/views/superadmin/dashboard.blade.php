@extends('layouts.app', ['title' => 'SaaS-Control-Center', 'heading' => 'SaaS-Control-Center', 'eyebrow' => 'Superadministrator'])
@section('content')
<div class="metrics">
    <div class="metric"><span>Mandanten</span><strong>{{ $metrics['tenants'] }}</strong><small>{{ $metrics['activeTenants'] }} aktiv</small></div>
    <div class="metric"><span>Benutzer</span><strong>{{ $metrics['users'] }}</strong><small>plattformweit</small></div>
    <div class="metric"><span>Pakete</span><strong>{{ $metrics['packages'] }}</strong><small>aktive Tarife</small></div>
    <div class="metric"><span>Verträge</span><strong>{{ $metrics['subscriptions'] }}</strong><small>Test + aktiv</small></div>
</div>
<section class="panel top-gap">
    <div class="panel-header"><div><span class="eyebrow">Mandanten</span><h2>Zuletzt angelegt</h2></div></div>
    @if($recentTenants->isEmpty())
        <div class="empty-state"><strong>Noch keine Taxiunternehmen angelegt.</strong><span>Der SaaS-Kern ist bereit für den ersten Mandanten.</span></div>
    @else
        <div class="table-wrap"><table><thead><tr><th>Unternehmen</th><th>Slug</th><th>Status</th><th>Erstellt</th></tr></thead><tbody>@foreach($recentTenants as $item)<tr><td><strong>{{ $item->name }}</strong><br><small>{{ $item->legal_name }}</small></td><td><code>{{ $item->slug }}</code></td><td><span class="status-pill {{ $item->status === 'active' ? 'success' : 'warning' }}">{{ ucfirst($item->status) }}</span></td><td>{{ $item->created_at?->format('d.m.Y H:i') }}</td></tr>@endforeach</tbody></table></div>
    @endif
</section>
@endsection
