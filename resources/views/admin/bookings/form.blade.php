@extends('layouts.admin')

@section('title', $booking->exists ? 'Modifica prenotazione' : 'Nuova prenotazione')

@section('content')
    <div style="max-width:680px">
        <div style="display:flex;gap:.75rem;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap">
            <a href="{{ route('admin.bookings.index') }}" class="btn btn--outline btn--sm">← Torna alle prenotazioni</a>

            @if ($booking->exists)
                <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                    @if($booking->isCanceled())
                        <span class="badge badge--canceled">Cancellata</span>
                        <form method="POST" action="{{ route('admin.bookings.restore', $booking) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn--outline btn--sm">Ripristina cancellazione</button>
                        </form>
                    @else
                        <span class="badge badge--booked">Attiva</span>
                        <form method="POST" action="{{ route('admin.bookings.cancel', $booking) }}"
                              onsubmit="return confirm('Segnare la prenotazione come cancellata e liberare i giorni?')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn--accent btn--sm">Segna cancellata</button>
                        </form>
                    @endif
                </div>
            @endif
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

                {{-- Financial --}}
                <div class="form-section-divider">
                    Dati economici
                    <span class="form-section-divider__note">(opzionali)</span>
                </div>

                <div class="form-row form-row--2col">
                    <div class="form-group">
                        <label class="form-label" for="income_amount">Incasso ricevuto (€)</label>
                        <input type="number" id="income_amount" name="income_amount" class="form-input"
                               value="{{ old('income_amount', $booking->income_amount) }}"
                               min="0" max="99999.99" step="0.01" placeholder="es. 850.00">
                        @error('income_amount') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="cleaning_amount">Pulizie (€)</label>
                        <input type="number" id="cleaning_amount" name="cleaning_amount" class="form-input"
                               value="{{ old('cleaning_amount', $booking->cleaning_amount) }}"
                               min="0" max="99999.99" step="0.01" placeholder="{{ config('apartment.booking.cleaning_fee') }}">
                        <span class="form-hint">Default: {{ config('apartment.booking.cleaning_fee') }}€</span>
                        @error('cleaning_amount') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="linen_amount">Biancheria (€)</label>
                        <input type="number" id="linen_amount" name="linen_amount" class="form-input"
                               value="{{ old('linen_amount', $booking->linen_amount) }}"
                               min="0" max="99999.99" step="0.01"
                               placeholder="{{ config('apartment.booking.linen_fee_per_person') }}">
                        <span class="form-hint">Default: {{ config('apartment.booking.linen_fee_per_person') }}€/ospite</span>
                        @error('linen_amount') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="parking_amount">Posto auto (€)</label>
                        <input type="number" id="parking_amount" name="parking_amount" class="form-input"
                               value="{{ old('parking_amount', $booking->parking_amount) }}"
                               min="0" max="99999.99" step="0.01"
                               placeholder="{{ config('apartment.booking.parking_fee_per_day') }}">
                        <span class="form-hint">Default: {{ config('apartment.booking.parking_fee_per_day') }}€/notte</span>
                        @error('parking_amount') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                @if (!$booking->exists)
                    <p class="form-hint" style="margin-top:-.25rem;margin-bottom:.75rem">
                        Lascia vuoto per compilare in seguito. I valori predefiniti verranno suggeriti in base al numero di ospiti.
                    </p>
                @endif

                {{-- Payment status --}}
                <div class="payment-table">
                    <div class="payment-table__header">
                        <span>Stato pagamenti</span>
                        <span>Data imputazione</span>
                    </div>

                    {{-- Incasso --}}
                    <div class="payment-table__row">
                        <label class="payment-table__check">
                            <input type="hidden" name="income_paid" value="0">
                            <input type="checkbox" id="income_paid" name="income_paid" value="1" class="form-checkbox"
                                   @checked(old('income_paid', $booking->income_paid ?? false))>
                            <span>Incasso pagato</span>
                        </label>
                        <div class="payment-table__date">
                            <input type="date" id="income_paid_at" name="income_paid_at" class="form-input"
                                   value="{{ old('income_paid_at', $booking->income_paid_at?->format('Y-m-d')) }}">
                            @error('income_paid_at') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Pulizie + Biancheria (data condivisa) --}}
                    <div class="payment-table__row">
                        <div class="payment-table__multi-check">
                            <label class="payment-table__check">
                                <input type="hidden" name="cleaning_paid" value="0">
                                <input type="checkbox" id="cleaning_paid" name="cleaning_paid" value="1" class="form-checkbox"
                                       @checked(old('cleaning_paid', $booking->cleaning_paid ?? false))>
                                <span>Pulizie pagate</span>
                            </label>
                            <label class="payment-table__check">
                                <input type="hidden" name="linen_paid" value="0">
                                <input type="checkbox" id="linen_paid" name="linen_paid" value="1" class="form-checkbox"
                                       @checked(old('linen_paid', $booking->linen_paid ?? false))>
                                <span>Biancheria pagata</span>
                            </label>
                        </div>
                        <div class="payment-table__date">
                            <span class="form-hint" style="margin-bottom:.2rem;display:block">Data pulizie / biancheria</span>
                            <input type="date" id="services_paid_at" name="services_paid_at" class="form-input"
                                   value="{{ old('services_paid_at', $booking->services_paid_at?->format('Y-m-d')) }}">
                            @error('services_paid_at') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Posto auto --}}
                    <div class="payment-table__row payment-table__row--last">
                        <label class="payment-table__check">
                            <input type="hidden" name="parking_paid" value="0">
                            <input type="checkbox" id="parking_paid" name="parking_paid" value="1" class="form-checkbox"
                                   @checked(old('parking_paid', $booking->parking_paid ?? false))>
                            <span>Posto auto incassato</span>
                        </label>
                        <div class="payment-table__date">
                            <span class="form-hint" style="margin-bottom:.2rem;display:block">Data parcheggio</span>
                            <input type="date" id="parking_paid_at" name="parking_paid_at" class="form-input"
                                   value="{{ old('parking_paid_at', $booking->parking_paid_at?->format('Y-m-d')) }}">
                            @error('parking_paid_at') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                @if(auth()->user()->hasPermission('view_accounting'))
                {{-- Tax declaration --}}
                <div class="payment-table" style="margin-top:.75rem">
                    <div class="payment-table__header" style="grid-template-columns:1fr">
                        <span>Dichiarazione dei redditi</span>
                    </div>
                    <div style="padding:.75rem 1rem">
                        <p class="form-hint" style="margin-bottom:.6rem">
                            Le voci selezionate compariranno nella
                            <a href="{{ route('admin.tax-declaration.index') }}" style="color:#30596C" target="_blank">dichiarazione dei redditi</a>
                            se il relativo pagamento risulta già incassato/pagato.
                        </p>
                        <div style="display:flex;flex-wrap:wrap;gap:.4rem 1.5rem">
                            <label class="payment-table__check">
                                <input type="hidden"   name="income_tax" value="0">
                                <input type="checkbox" name="income_tax" id="income_tax" value="1" class="form-checkbox"
                                       @checked(old('income_tax', $booking->exists ? $booking->income_tax : (bool) config('finance.tax_declaration_defaults.income', true)))>
                                <span>Incasso</span>
                            </label>
                            <label class="payment-table__check">
                                <input type="hidden"   name="cleaning_tax" value="0">
                                <input type="checkbox" name="cleaning_tax" id="cleaning_tax" value="1" class="form-checkbox"
                                       @checked(old('cleaning_tax', $booking->exists ? $booking->cleaning_tax : (bool) config('finance.tax_declaration_defaults.cleaning', true)))>
                                <span>Pulizie</span>
                            </label>
                            <label class="payment-table__check">
                                <input type="hidden"   name="linen_tax" value="0">
                                <input type="checkbox" name="linen_tax" id="linen_tax" value="1" class="form-checkbox"
                                       @checked(old('linen_tax', $booking->exists ? $booking->linen_tax : (bool) config('finance.tax_declaration_defaults.linen', true)))>
                                <span>Biancheria</span>
                            </label>
                            <label class="payment-table__check">
                                <input type="hidden"   name="parking_tax" value="0">
                                <input type="checkbox" name="parking_tax" id="parking_tax" value="1" class="form-checkbox"
                                       @checked(old('parking_tax', $booking->exists ? $booking->parking_tax : (bool) config('finance.tax_declaration_defaults.parking', false)))>
                                <span>Posto auto</span>
                            </label>
                        </div>
                    </div>
                </div>
                @endif

                <div style="display:flex;gap:.75rem;margin-top:.5rem">
                    <button type="submit" class="btn btn--primary">
                        {{ $booking->exists ? 'Salva modifiche' : 'Crea prenotazione' }}
                    </button>
                    <a href="{{ route('admin.bookings.index') }}" class="btn btn--outline">Annulla</a>
                </div>
            </form>
        </div>
    </div>

@push('scripts')
<script>
(function () {
    const DEFAULT_CLEANING = {{ config('apartment.booking.cleaning_fee') }};
    const DEFAULT_LINEN_PER_PERSON = {{ config('apartment.booking.linen_fee_per_person') }};
    const DEFAULT_PARKING_PER_DAY = {{ config('apartment.booking.parking_fee_per_day') }};

    const adults   = document.getElementById('adults');
    const children = document.getElementById('children');
    const linen    = document.getElementById('linen_amount');
    const cleaning = document.getElementById('cleaning_amount');
    const parking  = document.getElementById('parking_amount');
    const checkin  = document.getElementById('checkin');
    const checkout = document.getElementById('checkout');

    function totalGuests() {
        return (parseInt(adults?.value) || 0) + (parseInt(children?.value) || 0);
    }

    function totalNights() {
        if (!checkin?.value || !checkout?.value) return 0;
        const ci = new Date(checkin.value);
        const co = new Date(checkout.value);
        const diff = (co - ci) / (1000 * 60 * 60 * 24);
        return diff > 0 ? diff : 0;
    }

    function suggestDefaults() {
        // Only update placeholder if the field is currently empty (don't overwrite user input)
        if (!cleaning.value) {
            cleaning.placeholder = DEFAULT_CLEANING;
        }
        if (!linen.value) {
            const guests = totalGuests();
            linen.placeholder = guests > 0
                ? (DEFAULT_LINEN_PER_PERSON * guests).toFixed(2)
                : DEFAULT_LINEN_PER_PERSON;
        }
        if (!parking.value) {
            const nights = totalNights();
            parking.placeholder = nights > 0
                ? (DEFAULT_PARKING_PER_DAY * nights).toFixed(2)
                : DEFAULT_PARKING_PER_DAY;
        }
    }

    adults?.addEventListener('input', suggestDefaults);
    children?.addEventListener('input', suggestDefaults);
    checkin?.addEventListener('change', suggestDefaults);

    // Run once on load to set correct placeholder
    suggestDefaults();

    // Pre-fill payment dates with checkout date when checkout changes
    const incomePaidAt    = document.getElementById('income_paid_at');
    const servicesPaidAt  = document.getElementById('services_paid_at');

    function syncPaymentDates() {
        const val = checkout?.value;
        if (!val) return;
        if (incomePaidAt && !incomePaidAt.dataset.userEdited) {
            incomePaidAt.value = val;
        }
        if (servicesPaidAt && !servicesPaidAt.dataset.userEdited) {
            servicesPaidAt.value = val;
        }
    }

    // Mark as user-edited when the user manually changes the date
    incomePaidAt?.addEventListener('change', () => { incomePaidAt.dataset.userEdited = '1'; });
    servicesPaidAt?.addEventListener('change', () => { servicesPaidAt.dataset.userEdited = '1'; });

    checkout?.addEventListener('change', () => {
        syncPaymentDates();
        suggestDefaults();
    });

    // On load: if date fields are empty (new booking), pre-fill from checkout
    if (checkout?.value) {
        if (incomePaidAt && !incomePaidAt.value) {
            incomePaidAt.value = checkout.value;
        }
        if (servicesPaidAt && !servicesPaidAt.value) {
            servicesPaidAt.value = checkout.value;
        }
    }
})();
</script>
@endpush
@endsection

