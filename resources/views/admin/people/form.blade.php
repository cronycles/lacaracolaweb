@extends('layouts.admin')

@section('title', $person->exists ? 'Modifica ospite' : 'Nuovo ospite')

@section('content')
    <div style="max-width:680px">
        <div style="margin-bottom:1rem">
            <a href="{{ $returnTo ?? route('admin.people.index') }}" class="btn btn--outline btn--sm">← Torna agli ospiti</a>
        </div>

        <div class="a-card">
            <div class="a-card__title">{{ $person->exists ? 'Modifica ospite' : 'Nuovo ospite' }}</div>

            <form method="POST"
                  action="{{ $person->exists ? route('admin.people.update', $person) : route('admin.people.store') }}">
                @csrf
                @if ($person->exists)
                    @method('PUT')
                @endif
                <input type="hidden" name="return_to" value="{{ $returnTo ?? route('admin.people.index') }}">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="first_name">Nome</label>
                        <input type="text" id="first_name" name="first_name" class="form-input"
                               value="{{ old('first_name', $person->first_name) }}" maxlength="80" required>
                        @error('first_name') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="last_name">Cognome</label>
                        <input type="text" id="last_name" name="last_name" class="form-input"
                               value="{{ old('last_name', $person->last_name) }}" maxlength="80" required>
                        @error('last_name') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-input"
                               value="{{ old('email', $person->email) }}" maxlength="150">
                        @error('email') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone">Telefono</label>
                        <input type="text" id="phone" name="phone" class="form-input"
                               value="{{ old('phone', $person->phone) }}" maxlength="30">
                        @error('phone') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="birth_date">Data di nascita</label>
                        <input type="date" id="birth_date" name="birth_date" class="form-input"
                               value="{{ old('birth_date', $person->birth_date?->format('Y-m-d')) }}">
                        @error('birth_date') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="country_code">Paese (codice ISO)</label>
                        <select id="country_code" name="country_code" class="form-input">
                            <option value="">Seleziona un paese</option>
                            @foreach (config('apartment.guest_countries', []) as $countryCode => $countryName)
                                <option value="{{ $countryCode }}" @selected(old('country_code', $person->country_code) === $countryCode)>
                                    {{ $countryCode }} - {{ $countryName }}
                                </option>
                            @endforeach
                        </select>
                        @error('country_code') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="document_type">Tipo documento</label>
                        <input type="text" id="document_type" name="document_type" class="form-input"
                               value="{{ old('document_type', $person->document_type) }}" maxlength="30"
                               placeholder="CI / Passaporto / Patente">
                        @error('document_type') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="document_number">Numero documento</label>
                        <input type="text" id="document_number" name="document_number" class="form-input"
                               value="{{ old('document_number', $person->document_number) }}" maxlength="60">
                        @error('document_number') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                        <input type="checkbox" name="newsletter_subscribed" value="1"
                               @checked(old('newsletter_subscribed', $person->newsletter_subscribed))>
                        <span class="form-label" style="margin:0">Iscritto alla newsletter</span>
                    </label>
                </div>

                <div style="display:flex;gap:.75rem;margin-top:.5rem">
                    <button type="submit" class="btn btn--primary">
                        {{ $person->exists ? 'Salva modifiche' : 'Crea ospite' }}
                    </button>
                    <a href="{{ $returnTo ?? route('admin.people.index') }}" class="btn btn--outline">Annulla</a>
                </div>
            </form>
        </div>
    </div>
@endsection
