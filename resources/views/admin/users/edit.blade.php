@extends('layouts.admin')

@section('title', 'Modifica utente — ' . $user->name)

@section('content')
    <div style="max-width:600px">
        <div style="margin-bottom:1rem">
            <a href="{{ route('admin.users.index') }}" class="btn btn--outline btn--sm">← Utenti</a>
        </div>

        <div class="a-card">
            <div class="a-card__title">{{ $user->name }} <span style="color:#6b7f89;font-weight:400;font-size:.85rem">({{ $user->email }})</span></div>

            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label class="form-label" for="role_id">Ruolo</label>
                    <select id="role_id" name="role_id" class="form-select">
                        <option value="">— Nessun ruolo —</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>
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
                           value="{{ old('telegram_chat_id', $user->telegram_chat_id) }}"
                           placeholder="Lascia vuoto per non ricevere notifiche Telegram">
                    <div style="font-size:.78rem;color:#6b7f89;margin-top:.3rem">
                        L'utente deve inviare un messaggio al bot @LaCaracolaAndoraBot; il chat_id apparirà in <code>storage/logs/telegram.log</code>.
                    </div>
                    @error('telegram_chat_id')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group" style="margin-top:1.25rem">
                    <div class="form-label" style="margin-bottom:.5rem">
                        Permessi
                        <span style="font-weight:400;color:#6b7f89;font-size:.8rem">
                            (<code>manage_users</code> non è delegabile — solo super admin)
                        </span>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.35rem">
                        @foreach ($permissions as $permission)
                            @php
                                $fromRole  = in_array($permission->id, $rolePermissionIds);
                                $checked   = in_array($permission->id, old('permissions', $effectivePermissionIds));
                                $isDenied  = $fromRole && !$checked;
                            @endphp
                            <label style="display:flex;align-items:center;gap:.4rem;font-size:.875rem;cursor:pointer">
                                <input type="checkbox"
                                       name="permissions[]"
                                       value="{{ $permission->id }}"
                                       @checked($checked)>
                                <span>
                                    <strong>{{ $permission->name }}</strong>
                                    @if ($fromRole)
                                        <span style="font-size:.7rem;background:#dbeafe;color:#1d4ed8;border-radius:3px;padding:1px 5px;vertical-align:middle">ruolo</span>
                                    @endif
                                    @if ($isDenied)
                                        <span style="font-size:.7rem;background:#fee2e2;color:#b91c1c;border-radius:3px;padding:1px 5px;vertical-align:middle">negato</span>
                                    @endif
                                    <span style="color:#6b7f89"> — {{ $permission->description }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <p style="margin-top:.6rem;font-size:.78rem;color:#6b7f89">
                        I permessi con badge <span style="background:#dbeafe;color:#1d4ed8;border-radius:3px;padding:1px 5px">ruolo</span> sono assegnati dal ruolo.
                        Deselezionarli crea una negazione esplicita per questo utente.
                    </p>
                </div>

                <div style="display:flex;gap:.75rem;margin-top:1.25rem">
                    <button type="submit" class="btn btn--primary">Salva</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn--outline">Annulla</a>
                </div>
            </form>
        </div>
    </div>
@endsection
