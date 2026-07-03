@extends('layouts.admin')

@section('title', 'Segnalazione ospiti — Prenotazione #' . $booking->id)

@push('scripts')
<script>window.COMUNI_VALIDI = @json($comuniNames);</script>
<script>window.COUNTRIES_MAP = @json($countries);</script>
@endpush

@section('content')
    <div style="margin-bottom:1rem">
        <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn--outline btn--sm">← Torna alla prenotazione</a>
    </div>

    @php
        $allGuestsCount = $booking->allGuests()->count();
        $missingCount   = $booking->total_guests - $allGuestsCount;
        $totalPeople    = $booking->adults + ($booking->children ?? 0) + ($booking->babies ?? 0);
    @endphp

    {{-- Booking summary --}}
    <div class="a-card" style="margin-bottom:1.25rem">
        <div class="a-card__title">Prenotazione #{{ $booking->id }}</div>
        <div style="display:flex;gap:2rem;font-size:.875rem;flex-wrap:wrap">
            <div><strong>Ospite:</strong> {{ $booking->person->full_name }}</div>
            <div><strong>Arrivo:</strong> {{ $booking->checkin->format('d/m/Y') }}</div>
            <div><strong>Partenza:</strong> {{ $booking->checkout->format('d/m/Y') }}</div>
            <div><strong>Notti:</strong> {{ $booking->nights }}</div>
            <div>
                <strong>Persone:</strong> {{ $totalPeople }}
                (adulti: {{ $booking->adults }}{{ ($booking->children ?? 0) > 0 ? ', bambini: ' . $booking->children : '' }}{{ ($booking->babies ?? 0) > 0 ? ', neonati: ' . $booking->babies : '' }})
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="alert alert--success" style="margin-bottom:1rem">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert--error" style="margin-bottom:1rem">{{ session('error') }}</div>
    @endif

    {{-- Missing guests warning --}}
    @if ($missingCount > 0)
        <div class="a-card" style="margin-bottom:1.25rem;border-color:#f59e0b;background:#fffbeb">
            <div style="font-weight:600;color:#92400e;margin-bottom:.5rem">⚠️ Ospiti mancanti: {{ $missingCount }} person{{ $missingCount === 1 ? 'a' : 'e' }}</div>
            <p style="font-size:.875rem;color:#78350f;margin-bottom:.75rem">
                Sono presenti {{ $allGuestsCount }} ospiti su {{ $booking->total_guests }} previsti.
                Non è possibile testare né inviare finché non vengono aggiunti tutti gli ospiti.
            </p>
            @if(auth()->user()->hasPermission('manage_bookings'))
                <form method="POST" action="{{ route('admin.bookings.guests.store', $booking) }}"
                      style="display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap">
                    @csrf
                    <div class="form-group" style="flex:1;min-width:200px;margin:0">
                        <label class="form-label" for="missing-guest-add" style="font-size:.8rem">Aggiungi ospite esistente</label>
                        <select id="missing-guest-add" name="person_id" class="form-input" style="font-size:.875rem" required>
                            <option value="">— seleziona —</option>
                            @foreach ($selectablePeople as $p)
                                @unless ($booking->additionalGuests->contains('id', $p->id))
                                    <option value="{{ $p->id }}">{{ $p->full_name }}</option>
                                @endunless
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn--outline btn--sm" style="white-space:nowrap">+ Aggiungi</button>
                </form>
                <div style="margin-top:.5rem;font-size:.85rem">
                    <a href="{{ route('admin.people.create', ['attach_booking_id' => $booking->id]) }}"
                       style="color:#30596C">+ Crea nuovo ospite e aggiungilo a questa prenotazione</a>
                </div>
            @endif
        </div>
    @endif

    {{-- Per-row SOAP details (shown after test/send) --}}
    @if (session('row_details') && count(session('row_details')) > 0)
        <div class="a-card" style="margin-bottom:1.25rem">
            <div class="a-card__title">Dettaglio risposta per riga</div>
            <table class="a-table">
                <thead>
                    <tr><th>Riga</th><th>Esito</th><th>Descrizione</th></tr>
                </thead>
                <tbody>
                    @foreach (session('row_details') as $row)
                        <tr>
                            <td>{{ $row['row'] }}</td>
                            <td>{{ $row['esito'] }}</td>
                            <td>{{ $row['descrizione'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Guest forms --}}
    @php
        $guests = $booking->allGuests();
    @endphp

    <form id="guest-reporting-form" method="POST">
        @csrf

        @foreach ($guests as $i => $guest)
            @php
                $isFirst    = $i === 0;
                $isSingle   = $guests->count() === 1;
                $defaultTipo = $isSingle ? '16' : ($isFirst ? '18' : '20');
                $activeTipo  = old("guests.{$i}.tipo_alloggiato", $defaultTipo);
                $requiresDoc = in_array($activeTipo, ['16', '17', '18'], true);
            @endphp

            <div class="a-card" style="margin-bottom:1.25rem">
                <div class="a-card__title" style="display:flex;align-items:center;gap:.75rem">
                    <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;flex-shrink:0">
                        <input type="checkbox" name="guests[{{ $i }}][include]" value="1"
                               checked
                               data-guest-include-checkbox="{{ $i }}"
                               style="width:1rem;height:1rem">
                        <span>Includi</span>
                    </label>
                    <span>Ospite {{ $i + 1 }}: {{ $guest->full_name }}
                        @if($i === 0)
                            <span class="badge badge--outline" style="font-size:.7rem;margin-left:.35rem">Capogruppo</span>
                        @endif
                    </span>
                    <a href="{{ route('admin.people.edit', $guest) }}" class="btn btn--outline btn--sm" style="margin-left:auto"
                       target="_blank">Modifica profilo</a>
                </div>

                <input type="hidden" name="guests[{{ $i }}][person_id]" value="{{ $guest->id }}">

                <div class="form-row">
                    <div class="form-group" style="flex:2">
                        <label class="form-label" for="guests_{{ $i }}_tipo_alloggiato">Tipo alloggiato</label>
                        <select id="guests_{{ $i }}_tipo_alloggiato"
                                class="form-input" disabled
                                data-tipo-alloggiato-select>
                            @foreach ($guestTypes as $guestType)
                                <option value="{{ $guestType->code }}"
                                        @selected($activeTipo === $guestType->code)>
                                    {{ $guestType->code }} — {{ $guestType->name_it }}
                                    @unless($guestType->requires_document) (doc non obbligatori) @endunless
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="guests[{{ $i }}][tipo_alloggiato]" value="{{ $activeTipo }}">
                        @error("guests.{$i}.tipo_alloggiato") <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="guests_{{ $i }}_gender">Sesso *</label>
                        <select id="guests_{{ $i }}_gender" name="guests[{{ $i }}][gender]"
                                class="form-input" required>
                            <option value="">—</option>
                            <option value="M" @selected(old("guests.{$i}.gender", $guest->gender) === 'M')>Maschio</option>
                            <option value="F" @selected(old("guests.{$i}.gender", $guest->gender) === 'F')>Femmina</option>
                        </select>
                        @error("guests.{$i}.gender") <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="guests_{{ $i }}_nationality_code">Nazionalità *</label>
                        <input type="text" id="guests_{{ $i }}_nationality_code"
                               name="guests[{{ $i }}][nationality_code]" class="form-input" required
                               data-country-combo
                               data-current-value="{{ old("guests.{$i}.nationality_code", $guest->nationality_code) }}"
                               autocomplete="off"
                               placeholder="Cerca nazionalità...">
                        @error("guests.{$i}.nationality_code") <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        {{-- birth_date is read from Person.birth_date — shown read-only --}}
                        <label class="form-label">Data di nascita</label>
                        <input type="text" class="form-input" readonly
                               value="{{ $guest->birth_date?->format('d/m/Y') ?? '—' }}"
                               style="background:#f7f9fa">
                        <input type="hidden" name="guests[{{ $i }}][birth_date]"
                               value="{{ $guest->birth_date?->format('Y-m-d') ?? '' }}">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="guests_{{ $i }}_birth_country_code">Stato di nascita *</label>
                        <input type="text" id="guests_{{ $i }}_birth_country_code"
                               name="guests[{{ $i }}][birth_country_code]" class="form-input" required
                               data-country-combo
                               data-reporting-birth-country
                               data-current-value="{{ old("guests.{$i}.birth_country_code", $guest->birth_country_code) }}"
                               autocomplete="off"
                               placeholder="Cerca stato...">
                        @error("guests.{$i}.birth_country_code") <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group" id="birth_province_group_{{ $i }}" data-birth-province-group
                         style="{{ old("guests.{$i}.birth_country_code", $guest->birth_country_code) !== 'IT' ? 'display:none' : '' }}">
                        <label class="form-label" for="guests_{{ $i }}_birth_province">Provincia *</label>
                        <input type="text" id="guests_{{ $i }}_birth_province"
                               name="guests[{{ $i }}][birth_province]" class="form-input"
                               value="{{ old("guests.{$i}.birth_province", $guest->birth_province) }}"
                               maxlength="2" placeholder="Es: GE">
                        @error("guests.{$i}.birth_province") <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="guests_{{ $i }}_birth_municipality">Comune di nascita *</label>
                    <input type="text" id="guests_{{ $i }}_birth_municipality"
                           name="guests[{{ $i }}][birth_municipality]" class="form-input"
                           value="{{ old("guests.{$i}.birth_municipality", $guest->birth_municipality) }}"
                           maxlength="100" required
                           data-reporting-birth-municipality
                           data-current-value="{{ old("guests.{$i}.birth_municipality", $guest->birth_municipality) }}">
                    @error("guests.{$i}.birth_municipality") <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div data-document-fields-group
                     @unless($requiresDoc) style="display:none" @endunless>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="guests_{{ $i }}_document_type">Tipo documento *</label>
                            <select id="guests_{{ $i }}_document_type" name="guests[{{ $i }}][document_type]"
                                    class="form-input"
                                    data-document-required-field
                                    @if($requiresDoc) required @endif>
                                <option value="">Seleziona tipo</option>
                                <option value="passport" @selected(old("guests.{$i}.document_type", $guest->document_type) === 'passport')>Passaporto</option>
                                <option value="id_card" @selected(old("guests.{$i}.document_type", $guest->document_type) === 'id_card')>Carta d'identità</option>
                                <option value="driving_license" @selected(old("guests.{$i}.document_type", $guest->document_type) === 'driving_license')>Patente di guida</option>
                                <option value="residence_permit" @selected(old("guests.{$i}.document_type", $guest->document_type) === 'residence_permit')>Permesso di soggiorno</option>
                                <option value="other" @selected(old("guests.{$i}.document_type", $guest->document_type) === 'other')>Altro</option>
                            </select>
                            @error("guests.{$i}.document_type") <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="guests_{{ $i }}_document_number">Numero documento *</label>
                            <input type="text" id="guests_{{ $i }}_document_number"
                                   name="guests[{{ $i }}][document_number]" class="form-input"
                                   value="{{ old("guests.{$i}.document_number", $guest->document_number) }}"
                                   maxlength="60"
                                   data-document-required-field
                                   @if($requiresDoc) required @endif>
                            @error("guests.{$i}.document_number") <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="guests_{{ $i }}_document_issue_country_code">Stato rilascio doc. *</label>
                            <input type="text" id="guests_{{ $i }}_document_issue_country_code"
                                   name="guests[{{ $i }}][document_issue_country_code]" class="form-input"
                                   data-country-combo
                                   data-reporting-issue-country
                                   data-current-value="{{ old("guests.{$i}.document_issue_country_code", $guest->document_issue_country_code) }}"
                                   autocomplete="off"
                                   placeholder="Cerca stato..."
                                   @if($requiresDoc) required @endif>
                            @error("guests.{$i}.document_issue_country_code") <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group" data-document-issue-place-group
                             style="{{ old("guests.{$i}.document_issue_country_code", $guest->document_issue_country_code) !== 'IT' ? 'display:none' : '' }}">
                            <label class="form-label" for="guests_{{ $i }}_document_issue_place">Luogo rilascio doc.</label>
                            <input type="text" id="guests_{{ $i }}_document_issue_place"
                                   name="guests[{{ $i }}][document_issue_place]" class="form-input"
                                   value="{{ old("guests.{$i}.document_issue_place", $guest->document_issue_place) }}"
                                   maxlength="100"
                                   data-reporting-issue-municipality
                                   placeholder="Es: Genova">
                            @error("guests.{$i}.document_issue_place") <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Action buttons --}}
        <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center">
            @if ($missingCount > 0)
                <p style="font-size:.875rem;color:#dc2626;margin:0">
                    ⚠️ Aggiungi prima i {{ $missingCount }} ospiti mancanti per poter testare o inviare.
                </p>
            @else
                <button type="submit" form="guest-reporting-form"
                        formaction="{{ route('admin.guest-reporting.test', $booking) }}"
                        class="btn btn--outline">
                    🔍 Testa bozza (senza inviare)
                </button>
                <button type="submit" form="guest-reporting-form"
                        formaction="{{ route('admin.guest-reporting.send', $booking) }}"
                        class="btn btn--primary"
                        onclick="return confirm('Confermi l\'invio definitivo delle schedine alla Polizia di Stato?')">
                    📤 Invia definitivamente
                </button>
            @endif
        </div>
    </form>

    {{-- Submission history for this booking --}}
    @if ($booking->guestReports->isNotEmpty())
        <div class="a-card" style="margin-top:2rem">
            <div class="a-card__title">Storico invii per questa prenotazione</div>
            <table class="a-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Modalità</th>
                        <th>Stato</th>
                        <th>N° ospiti</th>
                        <th>Messaggio / Risposta servizio</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($booking->guestReports->sortByDesc('submitted_at') as $report)
                        <tr>
                            <td style="white-space:nowrap">{{ $report->submitted_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @if ($report->mode === 'test')
                                    <span class="badge badge--outline">Test</span>
                                @else
                                    <span class="badge badge--primary">Invio</span>
                                @endif
                            </td>
                            <td>
                                @if ($report->status === 'success')
                                    <span class="badge badge--success">Successo</span>
                                @else
                                    <span class="badge badge--error">Errore</span>
                                @endif
                            </td>
                            <td style="text-align:center">{{ $report->guests_count }}</td>
                            <td style="font-size:.8rem">
                                @if ($report->error_message)
                                    <div style="margin-bottom:.35rem">{{ $report->error_message }}</div>
                                @endif
                                @if ($report->soap_response)
                                    <details>
                                        <summary style="cursor:pointer;color:#30596C;font-size:.78rem">Risposta completa servizio</summary>
                                        <pre style="margin:.4rem 0 0;padding:.5rem;background:#f5f8fa;border-radius:4px;font-size:.72rem;overflow-x:auto;max-width:480px;white-space:pre-wrap;word-break:break-all">{{ json_encode($report->soap_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
