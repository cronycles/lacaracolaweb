@extends('layouts.admin')

@section('title', 'Impostazioni')

@section('content')
    <div style="max-width:600px">

        {{-- Success message --}}
        @if (session('success'))
            <div style="background:#d1fae5;border:1px solid #6ee7b7;border-radius:8px;padding:.75rem 1rem;margin-bottom:1.25rem;font-size:.875rem;color:#065f46">
                ✓ {{ session('success') }}
            </div>
        @endif

        {{-- Quick links card --}}
        <div style="background:#f3f4f6;border-radius:8px;padding:1rem;margin-bottom:1.5rem;display:flex;gap:1rem;flex-wrap:wrap">
            <a href="{{ route('admin.account-security') }}" class="btn btn--outline">
                🔐 Cambia Password
            </a>
        </div>

        {{-- Booking mode card --}}
        <div class="a-card">
            <div class="a-card__title">Modalità Prenotazione</div>

            <p style="font-size:.875rem;color:#6b7f89;margin-bottom:1.25rem">
                Scegli come i visitatori possono prenotare l'appartamento.
            </p>

            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')

                {{-- Mode selector --}}
                <div class="form-group">
                    <label style="display:flex;align-items:flex-start;gap:.75rem;padding:.85rem 1rem;border:2px solid {{ old('booking_mode', $bookingMode) === 'form' ? '#30596C' : '#dde3e8' }};border-radius:8px;cursor:pointer;margin-bottom:.75rem"
                           id="lbl-form">
                        <input type="radio" name="booking_mode" value="form"
                               {{ old('booking_mode', $bookingMode) === 'form' ? 'checked' : '' }}
                               style="margin-top:3px;accent-color:#30596C"
                               onchange="syncMode(this)">
                        <span>
                            <strong style="display:block;font-size:.9rem">Modulo di richiesta disponibilità</strong>
                            <span style="font-size:.8rem;color:#6b7f89">I visitatori compilano il form sul sito e ricevi una notifica email.</span>
                        </span>
                    </label>

                    <label style="display:flex;align-items:flex-start;gap:.75rem;padding:.85rem 1rem;border:2px solid {{ old('booking_mode', $bookingMode) === 'external' ? '#30596C' : '#dde3e8' }};border-radius:8px;cursor:pointer"
                           id="lbl-external">
                        <input type="radio" name="booking_mode" value="external"
                               {{ old('booking_mode', $bookingMode) === 'external' ? 'checked' : '' }}
                               style="margin-top:3px;accent-color:#30596C"
                               onchange="syncMode(this)">
                        <span>
                            <strong style="display:block;font-size:.9rem">Link esterno (Airbnb / Booking / Interhome)</strong>
                            <span style="font-size:.8rem;color:#6b7f89">I visitatori vengono indirizzati a una piattaforma esterna per prenotare.</span>
                        </span>
                    </label>
                </div>

                {{-- External URL field (shown only when mode = external) --}}
                <div id="external-url-group" class="form-group"
                     style="{{ old('booking_mode', $bookingMode) === 'external' ? '' : 'display:none' }}">
                    <label class="form-label" for="booking_external_url">URL della piattaforma esterna</label>
                    <input type="url" id="booking_external_url" name="booking_external_url"
                           class="form-input {{ $errors->has('booking_external_url') ? 'is-invalid' : '' }}"
                           value="{{ old('booking_external_url', $bookingExternalUrl) }}"
                           placeholder="https://www.airbnb.it/rooms/..." maxlength="500">
                    @error('booking_external_url')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                    <div style="font-size:.78rem;color:#6b7f89;margin-top:.35rem">
                        Incolla il link diretto all'annuncio su Airbnb, Booking.com, Interhome o altro.
                    </div>
                </div>

                @error('booking_mode')
                    <div class="form-error" style="margin-bottom:.75rem">{{ $message }}</div>
                @enderror

                <button type="submit" class="btn btn--primary">Salva impostazioni</button>
            </form>
        </div>

    </div>

    <script>
        function syncMode(radio) {
            const isExternal = radio.value === 'external';
            document.getElementById('external-url-group').style.display = isExternal ? '' : 'none';
            document.getElementById('lbl-form').style.borderColor     = isExternal ? '#dde3e8' : '#30596C';
            document.getElementById('lbl-external').style.borderColor = isExternal ? '#30596C' : '#dde3e8';
        }
    </script>
@endsection
