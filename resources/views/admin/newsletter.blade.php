@extends('layouts.admin')

@section('title', 'Newsletter')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem">
        <h1 style="font-size:1.1rem;font-weight:700;margin:0">Newsletter</h1>
        <a href="{{ route('admin.newsletter.templates.create') }}" class="btn btn--primary">Nuovo template</a>
    </div>

    <div class="a-card" style="margin-bottom:1rem">
        <div class="a-card__title">Template salvati</div>
        @if($templates->isEmpty())
            <p style="color:#6b7f89;font-size:.875rem">Nessun template salvato.</p>
        @else
            <div class="a-table-wrap"><table class="a-table">
                <thead><tr><th>Titolo</th><th>Oggetto</th><th>Stato</th><th>Azioni</th></tr></thead>
                <tbody>
                @foreach($templates as $template)
                    <tr>
                        <td>{{ $template->title }}</td><td>{{ $template->subject }}</td><td>{{ $template->archived_at ? 'Archiviato' : 'Attivo' }}</td>
                        <td style="display:flex;gap:.35rem;flex-wrap:wrap">
                            @if(!$template->archived_at)
                                <a class="btn btn--outline btn--sm" target="_blank" rel="noopener" href="{{ route('admin.newsletter.templates.preview', $template) }}">Anteprima</a>
                                <a class="btn btn--outline btn--sm" href="{{ route('admin.newsletter.templates.edit', $template) }}">Modifica / invia</a>
                                <form method="POST" action="{{ route('admin.newsletter.templates.test', $template) }}" title="Destinatario: {{ config('newsletter.test_recipient') }}">@csrf<button class="btn btn--outline btn--sm" type="submit">Invio di prova a {{ config('newsletter.test_recipient') }}</button></form>
                                <form method="POST" action="{{ route('admin.newsletter.templates.archive', $template) }}">@csrf<button class="btn btn--danger btn--sm" type="submit">Archivia</button></form>
                            @endif
                            <form method="POST" action="{{ route('admin.newsletter.templates.destroy', $template) }}" onsubmit="return confirm('Eliminare definitivamente questo template?');">@csrf @method('DELETE')<button class="btn btn--danger btn--sm" type="submit">Elimina</button></form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </div>

    <div class="a-card" style="margin-bottom:1rem">
        <div class="a-card__title">Cronologia invii</div>
        @if($campaigns->isEmpty())
            <p style="color:#6b7f89;font-size:.875rem">Nessun invio effettuato.</p>
        @else
            <div class="a-table-wrap"><table class="a-table">
                <thead><tr><th>Newsletter</th><th>Stato</th><th>Destinatari</th><th>Inviate</th><th>Data</th><th></th></tr></thead>
                <tbody>@foreach($campaigns as $campaign)
                    <tr><td>{{ $campaign->title }}</td><td>{{ $campaign->status }}</td><td>{{ $campaign->total_recipients }}</td><td>{{ $campaign->sent_deliveries }}</td><td>{{ $campaign->created_at?->format('d/m/Y H:i') }}</td><td><a class="btn btn--outline btn--sm" href="{{ route('admin.newsletter.campaigns.show', $campaign) }}">Dettagli</a></td></tr>
                @endforeach</tbody>
            </table></div>
        @endif
    </div>

    <div class="a-card" style="margin-bottom:1rem">
        <div class="a-card__title">Destinatari</div>
        <form method="GET" action="{{ route('admin.newsletter') }}" style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;align-items:flex-end">
            <div><label class="form-label" for="q">Cerca</label><input type="text" id="q" name="q" class="form-input" style="width:260px" placeholder="Nome, cognome o email" value="{{ request('q') }}"></div>
            <div><label class="form-label" for="filter">Filtra per tipo</label><select id="filter" name="filter" class="form-select" style="width:auto"><option value="">Tutti</option><option value="guests" @selected(request('filter') === 'guests')>Solo ospiti</option><option value="non_guests" @selected(request('filter') === 'non_guests')>Solo non-ospiti</option></select></div>
            <button type="submit" class="btn btn--outline">Filtra</button>
        </form>

        @php($activeTemplate = $templates->firstWhere('archived_at', null))
        @if($activeTemplate)
            <form method="POST" action="{{ route('admin.newsletter.templates.send.confirm', $activeTemplate) }}">
                @csrf
                <p style="font-size:.875rem;color:#6b7f89">Template attivo: <strong>{{ $activeTemplate->title }}</strong>. Per cambiare contenuto, usa “Modifica / invia” sopra.</p>
                <input type="hidden" name="q" value="{{ request('q') }}"><input type="hidden" name="filter" value="{{ request('filter') }}">
                <label style="display:block;margin-bottom:.75rem"><input type="checkbox" name="select_all" value="1"> Seleziona tutti gli iscritti validi del filtro</label>
                <div class="a-table-wrap"><table class="a-table"><thead><tr><th></th><th>Nome</th><th>Email</th><th>Telefono</th><th>Soggiorni</th><th>Iscritto dal</th><th></th></tr></thead><tbody>
                    @forelse($subscribers as $person)
                        @php($eligible = filled($person->email) && !$person->newsletter_opted_out)
                        <tr>
                            <td><input type="checkbox" name="person_ids[]" value="{{ $person->id }}" @disabled(!$eligible)></td>
                            <td><a href="{{ route('admin.people.show', $person) }}" style="color:#30596C;font-weight:600;text-decoration:none">{{ $person->full_name }}</a></td>
                            <td>{{ $person->email ?? '—' }}</td><td>{{ $person->phone_display ?? '—' }}</td><td>{{ $person->bookings_count }}</td><td>{{ $person->newsletter_subscribed_at?->format('d/m/Y') ?? '—' }}</td>
                            <td>@if(!$eligible)<span style="color:#9ca3af;font-size:.8rem">Nessuna email</span>@else<form method="POST" action="{{ route('admin.newsletter.toggle', $person) }}">@csrf @method('PATCH')<button type="submit" class="btn btn--danger btn--sm">Disiscrivi</button></form>@endif</td>
                        </tr>
                    @empty<tr><td colspan="7" style="color:#6b7f89">Nessun iscritto trovato.</td></tr>@endforelse
                </tbody></table></div>
                <div class="pagination-wrap">{{ $subscribers->appends(request()->query())->links() }}</div>
                <label class="form-label" for="manual_emails">Indirizzi manuali (uno per riga)</label>
                <textarea class="form-input" id="manual_emails" name="manual_emails" rows="3" placeholder="nome@example.com"></textarea>
                @error('recipients')<p class="form-error">{{ $message }}</p>@enderror
                @error('test_send')<p class="form-error">{{ $message }}</p>@enderror
                <button type="submit" class="btn btn--primary" style="margin-top:.75rem">Rivedi e conferma invio</button>
            </form>
        @else
            <p style="color:#6b7f89">Crea prima un template attivo per poter inviare una newsletter.</p>
        @endif
    </div>

    @if($suppressions->isNotEmpty())
        <div class="a-card"><div class="a-card__title">Indirizzi disiscritti</div><div class="a-table-wrap"><table class="a-table"><thead><tr><th>Email</th><th>Data</th><th></th></tr></thead><tbody>@foreach($suppressions as $suppression)<tr><td>{{ $suppression->email }}</td><td>{{ $suppression->suppressed_at?->format('d/m/Y H:i') }}</td><td><form method="POST" action="{{ route('admin.newsletter.suppressions.reactivate') }}">@csrf<input type="hidden" name="email" value="{{ $suppression->email }}"><button class="btn btn--outline btn--sm" type="submit">Riattiva</button></form></td></tr>@endforeach</tbody></table></div></div>
    @endif
@endsection
