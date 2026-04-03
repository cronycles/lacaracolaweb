@extends('layouts.admin')

@section('title', 'Ospiti')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h1 style="font-size:1.1rem;font-weight:700">Ospiti</h1>
        <a href="{{ route('admin.people.create') }}" class="btn btn--primary">+ Nuovo ospite</a>
    </div>

    {{-- Search bar --}}
    <form method="GET" action="{{ route('admin.people.index') }}" style="margin-bottom:1rem;display:flex;gap:.5rem">
        <input type="text" name="q" class="form-input" style="max-width:320px"
               placeholder="Cerca per nome, cognome o email…"
               value="{{ request('q') }}">
        <button type="submit" class="btn btn--outline">Cerca</button>
        @if (request('q'))
            <a href="{{ route('admin.people.index') }}" class="btn btn--outline">✕ Reset</a>
        @endif
    </form>

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
                        <th>Paese</th>
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
                            </td>
                            <td>{{ $person->email ?? '—' }}</td>
                            <td>{{ $person->phone ?? '—' }}</td>
                            <td>
                                @if ($person->country_flag)
                                    <span title="{{ $person->country_display }}" aria-label="{{ $person->country_display }}" style="font-size:1.1rem;line-height:1">
                                        {{ $person->country_flag }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td style="white-space:nowrap">
                                <a href="{{ route('admin.people.show', $person) }}" class="btn btn--outline btn--sm">Vedi</a>
                                <a href="{{ route('admin.people.edit', $person) }}" class="btn btn--outline btn--sm">Modifica</a>
                                <form method="POST" action="{{ route('admin.people.destroy', $person) }}"
                                      style="display:inline" onsubmit="return confirm('Eliminare questo ospite?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn--danger btn--sm">Elimina</button>
                                </form>
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
