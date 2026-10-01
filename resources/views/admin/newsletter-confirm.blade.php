@extends('layouts.admin')

@section('title', 'Conferma invio newsletter')

@section('content')
    <a href="{{ route('admin.newsletter') }}" class="btn btn--outline btn--sm">← Newsletter</a>
    <div class="a-card" style="margin-top:1rem;max-width:760px">
        <h1 style="font-size:1.1rem;margin-top:0">Conferma invio</h1>
        <p>Stai per inviare <strong>{{ $template->subject }}</strong> a <strong>{{ $recipients->count() }}</strong> destinatari.</p>
        <p style="color:#6b7f89;font-size:.875rem">Ogni destinatario riceverà un messaggio separato. Gli iscritti senza email o disiscritti sono stati esclusi.</p>
        <div class="a-table-wrap" style="margin:1rem 0"><table class="a-table"><thead><tr><th>Email</th><th>Tipo</th></tr></thead><tbody>
            @foreach($recipients as $recipient)
                <tr><td>{{ $recipient['email'] }}</td><td>{{ $recipient['person_id'] ? 'Iscritto' : 'Indirizzo manuale' }}</td></tr>
            @endforeach
        </tbody></table></div>
        <form method="POST" action="{{ route('admin.newsletter.templates.send', $template) }}">
            @csrf
            @foreach($requestData['person_ids'] ?? [] as $personId)<input type="hidden" name="person_ids[]" value="{{ $personId }}">@endforeach
            @if(!empty($requestData['select_all']))<input type="hidden" name="select_all" value="1">@endif
            <input type="hidden" name="q" value="{{ $requestData['q'] ?? '' }}">
            <input type="hidden" name="filter" value="{{ $requestData['filter'] ?? '' }}">
            <input type="hidden" name="manual_emails" value="{{ implode("\n", $manualEmails) }}">
            <div style="display:flex;gap:.5rem;flex-wrap:wrap"><a href="{{ route('admin.newsletter') }}" class="btn btn--outline">Annulla</a><button type="submit" class="btn btn--primary">Conferma e invia</button></div>
        </form>
    </div>
@endsection
