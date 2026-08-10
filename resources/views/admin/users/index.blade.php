@extends('layouts.admin')

@section('title', 'Gestione Utenti')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h1 style="font-size:1.1rem;font-weight:700">Utenti admin</h1>
        <a href="{{ route('admin.users.create') }}" class="btn btn--primary">+ Nuovo utente</a>
    </div>

    <div class="a-card">
        @if ($users->isEmpty())
            <p style="color:#6b7f89;font-size:.875rem">Nessun utente registrato.</p>
        @else
            <table class="a-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Telefono</th>
                        <th>Ruolo</th>
                        <th>Telegram Chat ID</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if ($user->phone)
                                    <a href="tel:{{ $user->phone }}">{{ $user->phone }}</a>
                                @else
                                    <span style="color:#6b7f89">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($user->role)
                                    <span class="badge badge--{{ $user->role->name }}">{{ $user->role->name }}</span>
                                @else
                                    <span style="color:#6b7f89">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($user->telegram_chat_id)
                                    <code style="font-size:.8rem">{{ $user->telegram_chat_id }}</code>
                                @else
                                    <span style="color:#6b7f89">—</span>
                                @endif
                            </td>
                            <td style="white-space:nowrap">
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn--outline btn--sm">Modifica</a>
                                @if ($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                          style="display:inline" onsubmit="return confirm('Eliminare questo utente?')">
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
        @endif
    </div>
@endsection
