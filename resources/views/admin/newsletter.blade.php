@extends('layouts.admin')

@section('title', 'Newsletter')

@section('content')
    <h1 style="font-size:1.1rem;font-weight:700;margin-bottom:1rem">Iscritti newsletter</h1>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.newsletter') }}"
          style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;align-items:flex-end">
        <div>
            <label class="form-label" for="q">Cerca</label>
            <input type="text" id="q" name="q" class="form-input" style="width:260px"
                   placeholder="Nome, cognome o email…" value="{{ request('q') }}">
        </div>
        <div>
            <label class="form-label" for="filter">Filtra per tipo</label>
            <select id="filter" name="filter" class="form-select" style="width:auto">
                <option value="">Tutti</option>
                <option value="guests"     @selected(request('filter') === 'guests')>Solo ospiti</option>
                <option value="non_guests" @selected(request('filter') === 'non_guests')>Solo non-ospiti</option>
            </select>
        </div>
        <button type="submit" class="btn btn--outline">Filtra</button>
        @if (request('q') || request('filter'))
            <a href="{{ route('admin.newsletter') }}" class="btn btn--outline">✕ Reset</a>
        @endif
    </form>

    <div class="a-card">
        @if ($subscribers->isEmpty())
            <p style="color:#6b7f89;font-size:.875rem">Nessun iscritto trovato.</p>
        @else
            <table class="a-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Soggiorni</th>
                        <th>Iscritto dal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($subscribers as $person)
                        <tr>
                            <td>
                                <a href="{{ route('admin.people.show', $person) }}"
                                   style="color:#30596C;font-weight:600;text-decoration:none">
                                    {{ $person->full_name }}
                                </a>
                            </td>
                            <td>{{ $person->email ?? '—' }}</td>
                            <td>{{ $person->bookings_count }}</td>
                            <td>
                                {{ $person->newsletter_subscribed_at?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td>
                                {{-- Toggle subscription --}}
                                <form method="POST"
                                      action="{{ route('admin.newsletter.toggle', $person) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn--danger btn--sm"
                                            onclick="return confirm('Disiscrivere {{ addslashes($person->full_name) }}?')">
                                        Disiscrivi
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="pagination-wrap">
                {{ $subscribers->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

    <p style="font-size:.8rem;color:#9ca3af;margin-top:.5rem">
        Per iscrivere manualmente un ospite vai alla sua scheda e modifica il campo "Newsletter".
    </p>
@endsection
