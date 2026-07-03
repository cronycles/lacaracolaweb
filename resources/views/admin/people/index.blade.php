@extends('layouts.admin')

@section('title', 'Ospiti')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h1 style="font-size:1.1rem;font-weight:700">Ospiti</h1>
        @if(auth()->user()->hasPermission('manage_people'))
            <a href="{{ route('admin.people.create') }}" class="btn btn--primary">+ Nuovo ospite</a>
        @endif
    </div>

    {{-- Search bar --}}
    <form method="GET" action="{{ route('admin.people.index') }}" style="margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
        <input type="text" name="q" class="form-input" style="max-width:320px"
               placeholder="Cerca per nome, cognome o email…"
               value="{{ request('q') }}">
        @if ($filter !== '')
            <input type="hidden" name="filter" value="{{ $filter }}">
        @endif
        <button type="submit" class="btn btn--outline">Cerca</button>
        @if (request('q') || $filter !== '')
            <a href="{{ route('admin.people.index') }}" class="btn btn--outline">✕ Reset</a>
        @endif
    </form>

    {{-- Filter tabs --}}
    <div style="margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap">
        <a href="{{ route('admin.people.index', array_filter(['q' => request('q')])) }}"
           class="btn btn--sm {{ $filter === '' ? 'btn--primary' : 'btn--outline' }}">Tutti</a>
        <a href="{{ route('admin.people.index', array_filter(['q' => request('q'), 'filter' => 'capogruppo'])) }}"
           class="btn btn--sm {{ $filter === 'capogruppo' ? 'btn--primary' : 'btn--outline' }}">Solo capogruppo</a>
        <a href="{{ route('admin.people.index', array_filter(['q' => request('q'), 'filter' => 'aggiuntivi'])) }}"
           class="btn btn--sm {{ $filter === 'aggiuntivi' ? 'btn--primary' : 'btn--outline' }}">Solo ospiti aggiuntivi</a>
    </div>

    <div class="a-card">
        @if ($people->isEmpty())
            <p style="color:#6b7f89;font-size:.875rem">Nessun ospite trovato.</p>
        @else
            <table class="a-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Telefono</th>
                        <th>Paese residenza</th>
                        <th>Nazionalità</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($people as $person)
                        <tr>
                            <td>
                                <a href="{{ route('admin.people.show', $person) }}"
                                   style="color:#30596C;font-weight:600;text-decoration:none">
                                    {{ $person->full_name }}
                                </a>
                                @if ($person->bookings_count > 0)
                                    <span style="display:inline-block;margin-left:.4rem;font-size:.7rem;font-weight:600;color:#1a5c44;background:#d1fae5;border-radius:4px;padding:1px 6px;vertical-align:middle;white-space:nowrap">capogruppo</span>
                                @else
                                    <span style="display:inline-block;margin-left:.4rem;font-size:.7rem;color:#6b7f89;background:#f1f5f9;border-radius:4px;padding:1px 6px;vertical-align:middle;white-space:nowrap">aggiuntivo</span>
                                @endif
                            </td>
                            <td>{{ $person->email ?? '—' }}</td>
                            <td>{{ $person->phone_display ?? '—' }}</td>
                            <td>
                                @if ($person->country_flag)
                                    <span title="{{ $person->country_display }}" aria-label="{{ $person->country_display }}" style="font-size:1.1rem;line-height:1">
                                        {{ $person->country_flag }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($person->nationality_flag)
                                    <span title="{{ $person->nationality_display }}" aria-label="{{ $person->nationality_display }}" style="font-size:1.1rem;line-height:1">
                                        {{ $person->nationality_flag }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td style="white-space:nowrap">
                                <a href="{{ route('admin.people.show', $person) }}" class="btn btn--outline btn--sm">Vedi</a>
                                @if(auth()->user()->hasPermission('manage_people'))
                                    <a href="{{ route('admin.people.edit', ['ospiti' => $person, 'return_to' => request()->fullUrl()]) }}" class="btn btn--outline btn--sm">Modifica</a>
                                    <form method="POST" action="{{ route('admin.people.destroy', $person) }}"
                                          style="display:inline" onsubmit="return confirm('Eliminare questo ospite?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn--danger btn--sm">Elimina</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="pagination-wrap">
                {{ $people->links() }}
            </div>
        @endif
    </div>
@endsection
