@extends('layouts.admin')

@section('title', 'Impostazioni')

@section('content')
    <div style="max-width:600px">

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

        <div style="margin-top:1.5rem">
            <h2 style="font-size:1.1rem;margin:0 0 .75rem">Calendari esterni</h2>

            @foreach($calendarProviders as $provider)
                @php($providerName = config("apartment.calendar.providers.{$provider->key}", $provider->key))
                <div class="a-card" style="margin-bottom:1rem">
                    <div style="display:flex;justify-content:space-between;gap:1rem;align-items:start;margin-bottom:1rem">
                        <div>
                            <div class="a-card__title">{{ $providerName }}</div>
                            <div style="font-size:.8rem;color:#6b7f89;margin-top:.25rem">
                                Stato: <strong>{{ $provider->sync_status === 'never_synced' ? 'Mai sincronizzato' : ucfirst($provider->sync_status) }}</strong>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.settings.calendar-providers.sync', $provider) }}">
                            @csrf
                            <button type="submit" class="btn btn--outline" {{ ! $provider->enabled || ! $provider->url ? 'disabled' : '' }}>Sincronizza</button>
                        </form>
                    </div>

                    <form method="POST" action="{{ route('admin.settings.calendar-providers.update', $provider) }}">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label class="form-label" for="calendar-url-{{ $provider->id }}">URL iCalendar</label>
                            <input type="url" id="calendar-url-{{ $provider->id }}" name="url" class="form-input" value="{{ old('url', $provider->url) }}" placeholder="https://.../calendar.ics" maxlength="500">
                            @error('url')
                                <div class="form-error">{{ $message }}</div>
                            @enderror
                        </div>
                        <label style="display:flex;align-items:center;gap:.5rem;font-size:.9rem;margin-bottom:1rem">
                            <input type="hidden" name="enabled" value="0">
                            <input type="checkbox" name="enabled" value="1" {{ old('url') === null ? ($provider->enabled ? 'checked' : '') : (old('enabled') ? 'checked' : '') }}>
                            Attivo per sincronizzazione e disponibilità
                        </label>
                        <button type="submit" class="btn btn--primary">Salva calendario</button>
                    </form>

                    <dl style="display:grid;grid-template-columns:auto 1fr;gap:.4rem .75rem;font-size:.8rem;color:#4b6470;margin:1rem 0 0">
                        <dt>Ultimo tentativo</dt><dd>{{ $provider->last_sync_attempt_at?->format('d/m/Y H:i') ?? 'Mai' }}</dd>
                        <dt>Ultimo successo</dt><dd>{{ $provider->last_successful_sync_at?->format('d/m/Y H:i') ?? 'Mai' }}</dd>
                        <dt>Eventi importati</dt><dd>{{ $provider->imported_event_count }}</dd>
                        @if($provider->latest_error)
                            <dt>Errore</dt><dd style="color:#b42318">{{ $provider->latest_error }}</dd>
                        @endif
                    </dl>
                </div>
            @endforeach
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
