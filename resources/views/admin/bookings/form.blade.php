@extends('layouts.admin')

@section('title', $booking->exists ? 'Modifica prenotazione' : 'Nuova prenotazione')

@section('content')
    <div style="max-width:680px">
        <div style="margin-bottom:1rem">
            <a href="{{ route('admin.bookings.index') }}" class="btn btn--outline btn--sm">← Torna alle prenotazioni</a>
        </div>

        <div class="a-card">
            <div class="a-card__title">{{ $booking->exists ? 'Modifica prenotazione' : 'Nuova prenotazione' }}</div>

            <form method="POST"
                  action="{{ $booking->exists ? route('admin.bookings.update', $booking) : route('admin.bookings.store') }}">
                @csrf
                @if ($booking->exists)
                    @method('PUT')
                @endif

                {{-- Guest --}}
                <div class="form-group">
                    <label class="form-label" for="person_id">Ospite</label>
                    <select id="person_id" name="person_id" class="form-select" required>
                        <option value="">— Seleziona ospite —</option>
                        @foreach ($people as $person)
                            <option value="{{ $person->id }}"
                                @selected(old('person_id', $booking->person_id) == $person->id)>
                                {{ $person->full_name }}
                                @if ($person->email) ({{ $person->email }}) @endif
                            </option>
                        @endforeach
                    </select>
                    @error('person_id') <div class="form-error">{{ $message }}</div> @enderror
                    <div style="margin-top:.4rem">
                        <a href="{{ route('admin.people.create') }}" style="font-size:.8rem;color:#30596C" target="_blank">
                            + Aggiungi nuovo ospite (si apre in nuova tab)
                        </a>
                    </div>
                </div>

                {{-- Dates --}}
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="checkin">Check-in</label>
                        <input type="date" id="checkin" name="checkin" class="form-input"
                               value="{{ old('checkin', $booking->checkin?->format('Y-m-d')) }}" required>
                        @error('checkin') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="checkout">Check-out</label>
                        <input type="date" id="checkout" name="checkout" class="form-input"
                               value="{{ old('checkout', $booking->checkout?->format('Y-m-d')) }}" required>
                        @error('checkout') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Guests count --}}
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="adults">Adulti</label>
                        <input type="number" id="adults" name="adults" class="form-input"
                               value="{{ old('adults', $booking->adults ?? 2) }}" min="1" max="6" required>
                        @error('adults') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="children">Bambini</label>
                        <input type="number" id="children" name="children" class="form-input"
                               value="{{ old('children', $booking->children ?? 0) }}" min="0" max="6">
                        @error('children') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="babies">Neonati</label>
                        <input type="number" id="babies" name="babies" class="form-input"
                               value="{{ old('babies', $booking->babies ?? 0) }}" min="0" max="6">
                        @error('babies') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="pets">Animali domestici</label>
                        <input type="number" id="pets" name="pets" class="form-input"
                               value="{{ old('pets', $booking->pets ?? 0) }}" min="0" max="4">
                        @error('pets') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Source --}}
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="source">Origine</label>
                        <select id="source" name="source" class="form-select" required>
                            @foreach (['direct', 'airbnb', 'booking', 'interhome'] as $src)
                                <option value="{{ $src }}" @selected(old('source', $booking->source) === $src)>
                                    {{ ucfirst($src) }}
                                </option>
                            @endforeach
                        </select>
                        @error('source') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="external_ref">Rif. esterno</label>
                        <input type="text" id="external_ref" name="external_ref" class="form-input"
                               value="{{ old('external_ref', $booking->external_ref) }}" maxlength="60">
                        @error('external_ref') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Notes --}}
                <div class="form-group">
                    <label class="form-label" for="notes">Note interne</label>
                    <textarea id="notes" name="notes" class="form-textarea" rows="3"
                              maxlength="1000">{{ old('notes', $booking->notes) }}</textarea>
                    @error('notes') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div style="display:flex;gap:.75rem;margin-top:.5rem">
                    <button type="submit" class="btn btn--primary">
                        {{ $booking->exists ? 'Salva modifiche' : 'Crea prenotazione' }}
                    </button>
                    <a href="{{ route('admin.bookings.index') }}" class="btn btn--outline">Annulla</a>
                </div>
            </form>
        </div>
    </div>
@endsection
