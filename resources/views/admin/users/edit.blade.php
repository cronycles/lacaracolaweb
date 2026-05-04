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

                <div class="form-group" style="margin-top:1.25rem">
                    <div class="form-label" style="margin-bottom:.5rem">
                        Override permessi individuali
                        <span style="font-weight:400;color:#6b7f89;font-size:.8rem">
                            (si sommano ai permessi del ruolo; <code>manage_users</code> non è delegabile)
                        </span>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.35rem">
                        @foreach ($permissions as $permission)
                            <label style="display:flex;align-items:center;gap:.4rem;font-size:.875rem;cursor:pointer">
                                <input type="checkbox"
                                       name="permissions[]"
                                       value="{{ $permission->id }}"
                                       @checked(in_array($permission->id, old('permissions', $user->permissionOverrides->pluck('id')->toArray())))>
                                <span>
                                    <strong>{{ $permission->name }}</strong>
                                    <span style="color:#6b7f89"> — {{ $permission->description }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div style="display:flex;gap:.75rem;margin-top:1.25rem">
                    <button type="submit" class="btn btn--primary">Salva</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn--outline">Annulla</a>
                </div>
            </form>
        </div>
    </div>
@endsection
