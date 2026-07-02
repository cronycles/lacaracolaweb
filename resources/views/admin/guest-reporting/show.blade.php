@extends('layouts.admin')

@section('title', 'Segnalazione ospiti — Prenotazione #' . $booking->id)

@section('content')
    <div style="margin-bottom:1rem">
        <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn--outline btn--sm">← Torna alla prenotazione</a>
    </div>

    {{-- Booking summary --}}
    <div class="a-card" style="margin-bottom:1.25rem">
        <div class="a-card__title">Prenotazione #{{ $booking->id }}</div>
        <div style="display:flex;gap:2rem;font-size:.875rem;flex-wrap:wrap">
            <div><strong>Ospite:</strong> {{ $booking->person->full_name }}</div>
            <div><strong>Arrivo:</strong> {{ $booking->checkin->format('d/m/Y') }}</div>
            <div><strong>Partenza:</strong> {{ $booking->checkout->format('d/m/Y') }}</div>
            <div><strong>Notti:</strong> {{ $booking->nights }}</div>
            <div><strong>Adulti:</strong> {{ $booking->adults }}</div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="alert alert--success" style="margin-bottom:1rem">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert--error" style="margin-bottom:1rem">{{ session('error') }}</div>
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
        $countryCodes = config('apartment.guest_countries', []);
        $tipoOptions = [
            '16' => '16 — Ospite singolo',
            '17' => '17 — Capo famiglia',
            '18' => '18 — Capo gruppo',
            '19' => '19 — Familiare (doc non obbligatori)',
            '20' => '20 — Membro gruppo (doc non obbligatori)',
        ];
    @endphp

    <form id="guest-reporting-form" method="POST">
        @csrf

        @foreach ($guests as $i => $guest)
            @php
                $isFirst = $i === 0;
                $defaultTipo = ($guest->birth_country_code === 'IT' || $guest->nationality_code === 'IT')
                    ? ($isFirst ? '16' : '17')
                    : ($isFirst ? '18' : '19');
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
                        <label class="form-label" for="guests_{{ $i }}_tipo_alloggiato">Tipo alloggiato *</label>
                        <select id="guests_{{ $i }}_tipo_alloggiato" name="guests[{{ $i }}][tipo_alloggiato]"
                                class="form-input" required>
                            @foreach ($tipoOptions as $val => $label)
                                <option value="{{ $val }}"
                                        @selected(old("guests.{$i}.tipo_alloggiato", $defaultTipo) === $val)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
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
                        <label class="form-label" for="guests_{{ $i }}_birth_country_code">Stato di nascita *</label>
                        <select id="guests_{{ $i }}_birth_country_code" name="guests[{{ $i }}][birth_country_code]"
                                class="form-input" required data-reporting-birth-country>
                            <option value="">Seleziona</option>
                            @foreach ($countryCodes as $code => $name)
                                <option value="{{ $code }}"
                                        @selected(old("guests.{$i}.birth_country_code", $guest->birth_country_code) === $code)>
                                    {{ $code }} - {{ $name }}
                                </option>
                            @endforeach
                        </select>
                        @error("guests.{$i}.birth_country_code") <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group" id="birth_province_group_{{ $i }}"
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

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="guests_{{ $i }}_nationality_code">Nazionalità *</label>
                        <select id="guests_{{ $i }}_nationality_code" name="guests[{{ $i }}][nationality_code]"
                                class="form-input" required>
                            <option value="">Seleziona</option>
                            @foreach ($countryCodes as $code => $name)
                                <option value="{{ $code }}"
                                        @selected(old("guests.{$i}.nationality_code", $guest->nationality_code) === $code)>
                                    {{ $code }} - {{ $name }}
                                </option>
                            @endforeach
                        </select>
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
                        <label class="form-label" for="guests_{{ $i }}_document_type">Tipo documento *</label>
                        <select id="guests_{{ $i }}_document_type" name="guests[{{ $i }}][document_type]"
                                class="form-input" required>
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
                               maxlength="60" required>
                        @error("guests.{$i}.document_number") <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="guests_{{ $i }}_document_issue_country_code">Stato rilascio doc. *</label>
                        <select id="guests_{{ $i }}_document_issue_country_code"
                                name="guests[{{ $i }}][document_issue_country_code]"
                                class="form-input" required>
                            <option value="">Seleziona</option>
                            @foreach ($countryCodes as $code => $name)
                                <option value="{{ $code }}"
                                        @selected(old("guests.{$i}.document_issue_country_code", $guest->document_issue_country_code) === $code)>
                                    {{ $code }} - {{ $name }}
                                </option>
                            @endforeach
                        </select>
                        @error("guests.{$i}.document_issue_country_code") <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="guests_{{ $i }}_document_issue_place">Luogo rilascio doc. *</label>
                        <input type="text" id="guests_{{ $i }}_document_issue_place"
                               name="guests[{{ $i }}][document_issue_place]" class="form-input"
                               value="{{ old("guests.{$i}.document_issue_place", $guest->document_issue_place) }}"
                               maxlength="100" required>
                        @error("guests.{$i}.document_issue_place") <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Action buttons --}}
        <div style="display:flex;gap:.75rem;flex-wrap:wrap">
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
