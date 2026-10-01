@extends('layouts.admin')

@section('title', 'Dettaglio invio newsletter')

@section('content')
    <a href="{{ route('admin.newsletter') }}" class="btn btn--outline btn--sm">← Newsletter</a>
    <div class="a-card" style="margin-top:1rem"><div class="a-card__title">{{ $campaign->title }}</div><p>Oggetto: {{ $campaign->subject }}</p><p>Stato: <strong>{{ $campaign->status }}</strong> · {{ $campaign->sent_count }}/{{ $campaign->total_recipients }} inviate · {{ $campaign->failed_count }} fallite</p><div style="display:flex;gap:.5rem"><a class="btn btn--outline btn--sm" target="_blank" rel="noopener" href="{{ route('admin.newsletter.campaigns.preview', $campaign) }}">Anteprima</a>@if($campaign->failed_count > 0)<form method="POST" action="{{ route('admin.newsletter.campaigns.retry', $campaign) }}">@csrf<button class="btn btn--primary btn--sm" type="submit">Riprova fallite</button></form>@endif</div></div>
    <div class="a-card"><div class="a-table-wrap"><table class="a-table"><thead><tr><th>Email</th><th>Persona</th><th>Stato</th><th>Data</th><th>Errore</th></tr></thead><tbody>@foreach($campaign->deliveries as $delivery)<tr><td>{{ $delivery->email }}</td><td>{{ $delivery->person?->full_name ?? 'Manuale' }}</td><td>{{ $delivery->status }}</td><td>{{ $delivery->sent_at?->format('d/m/Y H:i') ?? '—' }}</td><td>{{ $delivery->error ?? '—' }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
