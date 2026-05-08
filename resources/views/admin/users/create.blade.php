@extends('layouts.admin')

@section('title', 'Nuovo utente')

@section('content')
    <div style="max-width:520px">
        <div style="margin-bottom:1rem">
            <a href="{{ route('admin.users.index') }}" class="btn btn--outline btn--sm">← Utenti</a>
        </div>

        <div class="a-card">
            <div class="a-card__title">Crea nuovo utente</div>

            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="name">Nome</label>
                    <input type="text" id="name" name="name" class="form-input"
                           value="{{ old('name') }}" required autofocus>
                    @error('name')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-input"
                           value="{{ old('email') }}" required>
                    @error('email')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input"
                           required autocomplete="new-password">
                    @error('password')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="password_confirmation">Conferma password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="form-input" required autocomplete="new-password">
                </div>

                <div class="form-group">
                    <label class="form-label" for="role_id">Ruolo</label>
                    <select id="role_id" name="role_id" class="form-select">
                        <option value="">— Nessun ruolo —</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="telegram_chat_id">Telegram Chat ID</label>
                    <input type="text" id="telegram_chat_id" name="telegram_chat_id" class="form-input"
                           value="{{ old('telegram_chat_id') }}"
                           placeholder="Lascia vuoto; aggiorna dopo aver ricevuto il primo messaggio dal bot">
                    <div style="font-size:.78rem;color:#6b7f89;margin-top:.3rem">
                        L'utente deve inviare un messaggio al bot @LaCaracolaAndoraBot; il chat_id apparirà in <code>storage/logs/telegram.log</code>.
                    </div>
                    @error('telegram_chat_id')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div style="display:flex;gap:.75rem;margin-top:1.25rem">
                    <button type="submit" class="btn btn--primary">Crea utente</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn--outline">Annulla</a>
                </div>
            </form>
        </div>
    </div>
@endsection
