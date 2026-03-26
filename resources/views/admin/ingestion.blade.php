@extends('layouts.admin')

@section('title', 'Ingestion email')

@section('content')
    <div style="max-width:760px">

        {{-- Step 1: paste --}}
        <div class="a-card">
            <div class="a-card__title">📧 Incolla testo email di prenotazione</div>
            <p style="font-size:.875rem;color:#6b7f89;margin-bottom:1rem">
                Copia e incolla il testo dell'email di prenotazione (Interhome, Airbnb, Booking.com o manuale).
                Il sistema estrarrà automaticamente i dati principali.
            </p>

            <form method="POST" action="{{ route('admin.ingestion.parse') }}">
                @csrf
                <div class="form-group">
                    <textarea name="raw_text" class="form-textarea" rows="10"
                              placeholder="Incolla qui il testo dell'email…"
                              style="font-family:monospace;font-size:.8rem"
                              required>{{ old('raw_text', $rawText) }}</textarea>
                    @error('raw_text')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn--primary">Analizza email</button>
            </form>
        </div>

        @if ($parsed !== null)
            {{-- Step 2: confirm pre-filled form --}}
            <div class="a-card" style="border-top: 3px solid #30596C">
                <div class="a-card__title">✅ Dati estratti — verifica e conferma</div>

                {{-- Show existing person alert if found --}}
                @if ($existingPerson)
                    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#1e40af">
                        ℹ️ Ospite già registrato: <strong>{{ $existingPerson->full_name }}</strong>
                        ({{ $existingPerson->email }}).
                        La prenotazione verrà associata a quest'ospite.
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.ingestion.store') }}">
                    @csrf

                    {{-- Person section --}}
                    <div style="background:#f9fafb;border:1px solid #dde3e8;border-radius:6px;padding:1rem;margin-bottom:1rem">
                        <p style="font-size:.75rem;font-weight:700;text-transform:uppercase;color:#6b7f89;margin-bottom:.75rem">
                            Ospite {{ $existingPerson ? '(già esistente — i dati sotto non sovrascrivono)' : '(nuovo)' }}
                        </p>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="first_name">Nome *</label>
                                <input type="text" id="first_name" name="first_name" class="form-input"
                                       value="{{ old('first_name', $parsed['first_name']) }}" required maxlength="60">
                                @error('first_name') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="last_name">Cognome *</label>
                                <input type="text" id="last_name" name="last_name" class="form-input"
                                       value="{{ old('last_name', $parsed['last_name']) }}" required maxlength="60">
                                @error('last_name') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-input"
                                       value="{{ old('email', $parsed['email']) }}" maxlength="150">
                                @error('email') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="phone">Telefono</label>
                                <input type="text" id="phone" name="phone" class="form-input"
                                       value="{{ old('phone', $parsed['phone']) }}" maxlength="30">
                            </div>
                        </div>
                    </div>

                    {{-- Booking section --}}
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="checkin">Check-in *</label>
                            <input type="date" id="checkin" name="checkin" class="form-input"
                                   value="{{ old('checkin', $parsed['checkin']) }}" required>
                            @error('checkin') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="checkout">Check-out *</label>
                            <input type="date" id="checkout" name="checkout" class="form-input"
                                   value="{{ old('checkout', $parsed['checkout']) }}" required>
                            @error('checkout') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="adults">Adulti *</label>
                            <input type="number" name="adults" class="form-input"
                                   value="{{ old('adults', $parsed['adults']) }}" min="1" max="10" required>
                            @error('adults') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="children">Bambini</label>
                            <input type="number" name="children" class="form-input"
                                   value="{{ old('children', $parsed['children']) }}" min="0" max="6">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="babies">Neonati</label>
                            <input type="number" name="babies" class="form-input"
                                   value="{{ old('babies', 0) }}" min="0" max="6">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="source">Origine *</label>
                            <select name="source" class="form-select" required>
                                @foreach (['direct', 'airbnb', 'booking', 'interhome'] as $src)
                                    <option value="{{ $src }}"
                                        @selected(old('source', $parsed['source']) === $src)>
                                        {{ ucfirst($src) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="external_ref">Rif. esterno</label>
                            <input type="text" name="external_ref" class="form-input"
                                   value="{{ old('external_ref', $parsed['external_ref']) }}" maxlength="60">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="notes">Note interne (opzionale)</label>
                        <textarea name="notes" class="form-textarea" rows="3" maxlength="1000">{{ old('notes') }}</textarea>
                    </div>

                    <div style="display:flex;gap:.75rem;margin-top:.5rem">
                        <button type="submit" class="btn btn--primary">💾 Salva prenotazione</button>
                        <a href="{{ route('admin.ingestion') }}" class="btn btn--outline">Ricomincia</a>
                    </div>
                </form>
            </div>
        @endif
    </div>
@endsection
