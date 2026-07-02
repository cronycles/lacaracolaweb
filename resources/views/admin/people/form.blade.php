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
                @if (!empty($attachBookingId))
                    <input type="hidden" name="attach_booking_id" value="{{ $attachBookingId }}">
                @endif

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="first_name">Nome *</label>
                        <input type="text" id="first_name" name="first_name" class="form-input"
                               value="{{ old('first_name', $person->first_name) }}" maxlength="80" required>
                        @error('first_name') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="last_name">Cognome *</label>
                        <input type="text" id="last_name" name="last_name" class="form-input"
                               value="{{ old('last_name', $person->last_name) }}" maxlength="80" required>
                        @error('last_name') <div class="form-error">{{ $message }}</div> @enderror
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
                        <label class="form-label" for="gender">Sesso</label>
                        <select id="gender" name="gender" class="form-input">
                            <option value="">—</option>
                            <option value="M" @selected(old('gender', $person->gender) === 'M')>Maschio</option>
                            <option value="F" @selected(old('gender', $person->gender) === 'F')>Femmina</option>
                        </select>
                        @error('gender') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="country_code">Paese di residenza</label>
                        <select id="country_code" name="country_code" class="form-input">
                            <option value="">Seleziona</option>
                            @foreach (config('apartment.guest_countries', []) as $countryCode => $countryName)
                                <option value="{{ $countryCode }}" @selected(old('country_code', $person->country_code) === $countryCode)>
                                    {{ $countryName }}
                                </option>
                            @endforeach
                        </select>
                        @error('country_code') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="nationality_code">Nazionalità</label>
                        <select id="nationality_code" name="nationality_code" class="form-input">
                            <option value="">Seleziona</option>
                            @foreach (config('apartment.guest_countries', []) as $code => $name)
                                <option value="{{ $code }}" @selected(old('nationality_code', $person->nationality_code) === $code)>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                        @error('nationality_code') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <hr style="margin:1.25rem 0;border:none;border-top:1px solid #e0e8ec">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="birth_country_code">Paese di nascita</label>
                        <select id="birth_country_code" name="birth_country_code" class="form-input"
                                data-reporting-birth-country>
                            <option value="">Seleziona</option>
                            @foreach (config('apartment.guest_countries', []) as $code => $name)
                                <option value="{{ $code }}" @selected(old('birth_country_code', $person->birth_country_code) === $code)>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                        @error('birth_country_code') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group" id="birth_province_group" data-birth-province-group
                         style="{{ old('birth_country_code', $person->birth_country_code) !== 'IT' ? 'display:none' : '' }}">
                        <label class="form-label" for="birth_province">Provincia di nascita</label>
                        <input type="text" id="birth_province" name="birth_province" class="form-input"
                               value="{{ old('birth_province', $person->birth_province) }}"
                               maxlength="2" placeholder="Es: GE">
                        @error('birth_province') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="birth_municipality">
                        {{ old('birth_country_code', $person->birth_country_code) === 'IT' ? 'Comune di nascita' : 'Città di nascita' }}
                    </label>
                    <input type="text" id="birth_municipality" name="birth_municipality" class="form-input"
                           value="{{ old('birth_municipality', $person->birth_municipality) }}"
                           maxlength="100"
                           placeholder="{{ old('birth_country_code', $person->birth_country_code) === 'IT' ? 'Es: Genova' : 'Es: München' }}"
                           data-reporting-birth-municipality
                           data-current-value="{{ old('birth_municipality', $person->birth_municipality) }}">
                    @error('birth_municipality') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <hr style="margin:1.25rem 0;border:none;border-top:1px solid #e0e8ec">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="document_type">Tipo documento</label>
                        <select id="document_type" name="document_type" class="form-input">
                            <option value="">Seleziona</option>
                            <option value="passport" @selected(old('document_type', $person->document_type) === 'passport')>Passaporto</option>
                            <option value="id_card" @selected(old('document_type', $person->document_type) === 'id_card')>Carta d'identità</option>
                            <option value="driving_license" @selected(old('document_type', $person->document_type) === 'driving_license')>Patente di guida</option>
                            <option value="residence_permit" @selected(old('document_type', $person->document_type) === 'residence_permit')>Permesso di soggiorno</option>
                            <option value="other" @selected(old('document_type', $person->document_type) === 'other')>Altro</option>
                        </select>
                        @error('document_type') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="document_number">Numero documento</label>
                        <input type="text" id="document_number" name="document_number" class="form-input"
                               value="{{ old('document_number', $person->document_number) }}" maxlength="60">
                        @error('document_number') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="document_issue_country_code">Paese di rilascio</label>
                        <select id="document_issue_country_code" name="document_issue_country_code" class="form-input"
                                data-reporting-issue-country>
                            <option value="">Seleziona</option>
                            @foreach (config('apartment.guest_countries', []) as $code => $name)
                                <option value="{{ $code }}" @selected(old('document_issue_country_code', $person->document_issue_country_code) === $code)>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                        @error('document_issue_country_code') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group" id="document_issue_place_group" data-document-issue-place-group
                         style="{{ old('document_issue_country_code', $person->document_issue_country_code) !== 'IT' ? 'display:none' : '' }}">
                        <label class="form-label" for="document_issue_place">Comune di rilascio</label>
                        <input type="text" id="document_issue_place" name="document_issue_place" class="form-input"
                               value="{{ old('document_issue_place', $person->document_issue_place) }}"
                               maxlength="100" placeholder="Es: Genova"
                               data-reporting-issue-municipality>
                        @error('document_issue_place') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <hr style="margin:1.25rem 0;border:none;border-top:1px solid #e0e8ec">

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
