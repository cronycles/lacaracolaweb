@extends('layouts.admin')

@section('title', 'Modifica utente — ' . $user->name)

@section('content')
    <div style="max-width:600px">
        <div style="margin-bottom:1rem">
            <a href="{{ route('admin.users.index') }}" class="btn btn--outline btn--sm">← Utenti</a>
        </div>

        <div class="a-card">
            <div class="a-card__title">{{ $user->name }}</div>

            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-input"
                           value="{{ old('email', $user->email) }}" required autocomplete="email">
                    @error('email')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <div class="form-label" style="margin-bottom:.5rem">Dati host owner e pagamento</div>
                    <input type="text" name="tax_code" class="form-input" value="{{ old('tax_code', $user->tax_code) }}" placeholder="Codice fiscale">
                    <input type="text" name="address_street" class="form-input" value="{{ old('address_street', $user->address_street) }}" placeholder="Indirizzo (via e numero civico)" style="margin-top:.5rem">
                    <div class="form-row" style="margin-top:.5rem">
                        <input type="text" name="address_zip" class="form-input" value="{{ old('address_zip', $user->address_zip) }}" placeholder="CAP">
                        <input type="text" name="address_city" class="form-input" value="{{ old('address_city', $user->address_city) }}" placeholder="Città">
                    </div>
                    <input type="text" name="payment_beneficiary" class="form-input" value="{{ old('payment_beneficiary', $user->payment_beneficiary) }}" placeholder="Beneficiario" style="margin-top:.5rem">
                    <input type="text" name="payment_iban" class="form-input" value="{{ old('payment_iban', $user->payment_iban) }}" placeholder="IBAN" style="margin-top:.5rem">
                    <input type="text" name="payment_bic" class="form-input" value="{{ old('payment_bic', $user->payment_bic) }}" placeholder="BIC/SWIFT" style="margin-top:.5rem">
                    <label style="display:flex;align-items:center;gap:.5rem;margin-top:.6rem;cursor:pointer">
                        <input type="hidden" name="payment_enabled" value="0">
                        <input type="checkbox" name="payment_enabled" value="1" @checked(old('payment_enabled', $user->payment_enabled))>
                        <span>Abilita questo host owner per i pagamenti</span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Telefono</label>
                    <input type="tel" id="phone" name="phone" class="form-input"
                           value="{{ old('phone', $user->phone) }}" placeholder="Es. +39 333 1234567">
                    @error('phone')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

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

                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                        <input type="hidden" name="telegram_notifications_enabled" value="0">
                        <input type="checkbox" name="telegram_notifications_enabled" value="1"
                               @checked(old('telegram_notifications_enabled', $user->telegram_notifications_enabled))>
                        <span class="form-label" style="margin:0">Abilita notifiche Telegram</span>
                    </label>
                    <div style="font-size:.78rem;color:#6b7f89;margin-top:.3rem">
                        Se cambi ruolo, il flag viene riallineato automaticamente al valore predefinito del nuovo ruolo.
                    </div>
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
