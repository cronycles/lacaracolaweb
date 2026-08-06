@extends('layouts.admin')

@section('title', 'Prenotazione — ' . $booking->person->full_name)

@section('content')
    <div style="max-width:680px">
        <div style="display:flex;gap:.75rem;margin-bottom:1rem;align-items:center">
            <a href="{{ route('admin.bookings.index') }}" class="btn btn--outline btn--sm">← Prenotazioni</a>
            @if(auth()->user()->hasPermission('manage_bookings'))
                <a href="{{ route('admin.bookings.edit', $booking) }}" class="btn btn--primary btn--sm">Modifica</a>
                <button type="button" class="btn btn--outline btn--sm"
                        id="btn-telegram-notify"
                        data-url="{{ route('admin.bookings.notify-telegram', $booking) }}"
                        title="Invia notifica Telegram a tutti i destinatari configurati">
                    ✈ Telegram
                </button>
                <form method="POST" action="{{ route('admin.bookings.send-confirmation', $booking) }}"
                      class="js-confirm-send-mail"
                      data-confirm-message="{{ $booking->confirmation_sent_at ? 'Email di conferma già inviata il '.$booking->confirmation_sent_at->format('d/m/Y H:i').'. Inviare di nuovo?' : "Inviare l'email di conferma prenotazione con le istruzioni di pagamento?" }}">
                    @csrf
                    <button type="submit" class="btn btn--outline btn--sm" title="Invia email di conferma prenotazione con istruzioni di pagamento (48h) e scadenza cancellazione gratuita">
                        📧 Invia conferma{{ $booking->confirmation_sent_at ? ' (già inviata)' : '' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.bookings.destroy', $booking) }}"
                      onsubmit="return confirm('Eliminare questa prenotazione?')" style="margin-left:auto">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn--danger btn--sm">Elimina</button>
                </form>
            @endif
        </div>

        <div id="telegram-toast" style="display:none;margin-bottom:.75rem"></div>

        <div class="a-card">
            <div class="a-card__title">Dati prenotazione</div>
            <table class="a-table">
                <tbody>
                    <tr>
                        <th style="width:160px">Ospite</th>
                        <td>
                            <a href="{{ route('admin.people.show', $booking->person) }}"
                               style="color:#30596C;font-weight:600;text-decoration:none">
                                {{ $booking->person->full_name }}
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th>Stato</th>
                        <td>
                            @if($booking->isCanceled())
                                <span class="badge badge--canceled">Cancellata</span>
                                @if($booking->canceled_at)
                                    <span style="font-size:.8rem;color:#6b7f89;margin-left:.45rem">il {{ $booking->canceled_at->format('d/m/Y H:i') }}</span>
                                @endif
                            @else
                                <span class="badge badge--booked">Attiva</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Check-in</th>
                        <td>{{ $booking->checkin->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <th>Check-out</th>
                        <td>{{ $booking->checkout->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <th>Notti</th>
                        <td>{{ $booking->nights }}</td>
                    </tr>
                    <tr>
                        <th>Posti letto usati</th>
                        <td>{{ $booking->total_guests }}
                            (adulti: {{ $booking->adults }},
                             bambini: {{ $booking->children ?? 0 }})
                        </td>
                    </tr>
                    <tr>
                        <th>Neonati</th>
                        <td>{{ $booking->babies ?? 0 }}</td>
                    </tr>
                    <tr>
                        <th>Animali</th>
                        <td>{{ $booking->pets ?? 0 }}</td>
                    </tr>
                    <tr>
                        <th>Origine</th>
                        <td><span class="badge badge--{{ $booking->source }}">{{ $booking->source }}</span></td>
                    </tr>
                    @if ($booking->external_ref)
                        <tr>
                            <th>Rif. esterno</th>
                            <td>{{ $booking->external_ref }}</td>
                        </tr>
                    @endif
                    @if ($booking->notes)
                        <tr>
                            <th>Note interne</th>
                            <td style="white-space:pre-line">{{ $booking->notes }}</td>
                        </tr>
                    @endif
                    <tr>
                        <th>Creata il</th>
                        <td>{{ $booking->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if ($booking->bookingRequest)
            <div class="a-card" style="margin-top:1.25rem">
                <div class="a-card__title">Richiesta originale</div>
                <table class="a-table">
                    <tbody>
                        <tr>
                            <th style="width:160px">Inviata il</th>
                            <td>{{ $booking->bookingRequest->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @if ($booking->bookingRequest->message)
                            <tr>
                                <th>Messaggio</th>
                                <td style="white-space:pre-line">{{ $booking->bookingRequest->message }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Additional guests (booking_person pivot) --}}
        <div class="a-card" style="margin-top:1.25rem">
            <div class="a-card__title" style="display:flex;align-items:center;justify-content:space-between">
                <span>Ospiti della prenotazione</span>
            </div>

            {{-- Primary guest (read-only) --}}
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid #e8edf0">
                <div>
                    <a href="{{ route('admin.people.show', $booking->person) }}"
                       style="color:#30596C;font-weight:600;text-decoration:none">
                        {{ $booking->person->full_name }}
                    </a>
                    <span class="badge badge--outline" style="font-size:.7rem;margin-left:.5rem">Capogruppo</span>
                </div>
            </div>

            {{-- Additional guests --}}
            @foreach ($booking->additionalGuests as $guest)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid #e8edf0">
                    <a href="{{ route('admin.people.show', $guest) }}"
                       style="color:#30596C;font-weight:600;text-decoration:none">
                        {{ $guest->full_name }}
                    </a>
                    @if(auth()->user()->hasPermission('manage_bookings'))
                        <form method="POST"
                              action="{{ route('admin.bookings.guests.destroy', [$booking, $guest]) }}"
                              onsubmit="return confirm('Rimuovere {{ $guest->full_name }} dalla prenotazione?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn--danger btn--sm">Rimuovi</button>
                        </form>
                    @endif
                </div>
            @endforeach

            {{-- Add guest form --}}
            @if(auth()->user()->hasPermission('manage_bookings'))
                <div style="margin-top:.75rem">
                    @error('person_id')
                        <div class="alert alert--error" style="margin-bottom:.5rem;font-size:.85rem">{{ $message }}</div>
                    @enderror
                    @if((1 + $booking->additionalGuests->count()) < $booking->total_guests)
                        <form method="POST" action="{{ route('admin.bookings.guests.store', $booking) }}"
                              style="display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap">
                            @csrf
                            <div class="form-group" style="flex:1;min-width:200px;margin:0">
                                <label class="form-label" for="guest-person-add" style="font-size:.8rem">Aggiungi ospite esistente</label>
                                <select id="guest-person-add" name="person_id" class="form-input" style="font-size:.875rem" required>
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
                               style="color:#30596C">
                                + Crea nuovo ospite e aggiungilo a questa prenotazione
                            </a>
                        </div>
                    @else
                        <p style="font-size:.85rem;color:#666;margin:0">Numero massimo di ospiti raggiunto ({{ $booking->total_guests }} persone).</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Financial summary --}}
        <div class="a-card" style="margin-top:1.25rem">
            <div class="a-card__title">Dati economici</div>
            <table class="a-table">
                <tbody>
                    @if(auth()->user()->hasPermission('view_accounting'))
                    <tr>
                        <th style="width:160px">Incasso ricevuto</th>
                        <td>
                            @if ($booking->income_amount !== null)
                                <span class="badge badge--{{ $booking->income_paid ? 'paid' : 'unpaid' }}"
                                      title="{{ $booking->income_paid ? 'Incassato' : 'Da incassare' }}">
                                    € {{ number_format((float)$booking->income_amount, 2, ',', '.') }}
                                </span>
                                <span style="font-size:.8rem;color:#6b7f89;margin-left:.35rem">
                                    {{ $booking->income_paid ? 'Incassato' : 'Da incassare' }}
                                </span>
                            @else
                                <span style="color:#6b7f89">—</span>
                            @endif
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <th style="width:160px">Pulizie</th>
                        <td>
                            @if ($booking->cleaning_amount !== null)
                                <span class="badge badge--{{ $booking->cleaning_paid ? 'paid' : 'unpaid' }}"
                                      title="{{ $booking->cleaning_paid ? 'Pagate' : 'Da pagare' }}">
                                    € {{ number_format((float)$booking->cleaning_amount, 2, ',', '.') }}
                                </span>
                                <span style="font-size:.8rem;color:#6b7f89;margin-left:.35rem">
                                    {{ $booking->cleaning_paid ? 'Pagate' : 'Da pagare' }}
                                </span>
                            @else
                                <span style="color:#6b7f89">—</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Biancheria</th>
                        <td>
                            @if ($booking->linen_amount !== null)
                                <span class="badge badge--{{ $booking->linen_paid ? 'paid' : 'unpaid' }}"
                                      title="{{ $booking->linen_paid ? 'Pagata' : 'Da pagare' }}">
                                    € {{ number_format((float)$booking->linen_amount, 2, ',', '.') }}
                                </span>
                                <span style="font-size:.8rem;color:#6b7f89;margin-left:.35rem">
                                    {{ $booking->linen_paid ? 'Pagata' : 'Da pagare' }}
                                </span>
                            @else
                                <span style="color:#6b7f89">—</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Posto auto</th>
                        <td>
                            @if ($booking->parking_amount !== null)
                                <span class="badge badge--{{ $booking->parking_paid ? 'paid' : 'unpaid' }}"
                                      title="{{ $booking->parking_paid ? 'Incassato' : 'Da incassare' }}">
                                    € {{ number_format((float)$booking->parking_amount, 2, ',', '.') }}
                                </span>
                                <span style="font-size:.8rem;color:#6b7f89;margin-left:.35rem">
                                    {{ $booking->parking_paid ? 'Incassato' : 'Da incassare' }}
                                </span>
                            @else
                                <span style="color:#6b7f89">—</span>
                            @endif
                        </td>
                    </tr>
                    @if(auth()->user()->hasPermission('view_accounting'))
                    @if ($booking->total_expenses !== null)
                        <tr>
                            <th>Pulizie / Biancheria</th>
                            <td>
                                <span style="font-size:.85rem;color:#6b7f89" title="Importo raccolto dall'ospite e pagato al fornitore (passaggio). Non incide sul saldo netto.">
                                    € {{ number_format($booking->total_expenses, 2, ',', '.') }} (pass-through)
                                </span>
                            </td>
                        </tr>
                    @endif
                    @if ($booking->income_amount !== null)
                        <tr>
                            <th>Saldo netto</th>
                            <td>
                                @php $net = (float)$booking->income_amount + (float)($booking->parking_amount ?? 0); @endphp
                                <strong style="color:{{ $net >= 0 ? '#2e7d32' : '#c62828' }}">
                                    € {{ number_format($net, 2, ',', '.') }}
                                </strong>
                            </td>
                        </tr>
                    @endif
                    @endif
                </tbody>
            </table>

        </div>

        {{-- Tax declaration section --}}
        @if(auth()->user()->hasPermission('view_accounting'))
            @php
                $hasTaxItems = $booking->income_amount !== null
                    || $booking->cleaning_amount !== null
                    || $booking->linen_amount !== null
                    || $booking->parking_amount !== null;
            @endphp
            @if($hasTaxItems)
                <div class="a-card" style="margin-top:1.25rem">
                    <div class="a-card__title">Dichiarazione dei redditi</div>
                    <p style="font-size:.8rem;color:#6b7f89;margin-bottom:.75rem">
                        Le voci marcate compariranno nella
                        <a href="{{ route('admin.tax-declaration.index') }}" style="color:#30596C">dichiarazione dei redditi</a>
                        se il relativo pagamento è già incassato/pagato.
                        Per modificare i flag usa
                        @if(auth()->user()->hasPermission('manage_bookings'))
                            <a href="{{ route('admin.bookings.edit', $booking) }}" style="color:#30596C">Modifica prenotazione</a>.
                        @else
                            la pagina di modifica prenotazione.
                        @endif
                    </p>
                    <div style="display:flex;flex-wrap:wrap;gap:.5rem 1.5rem">
                        @if($booking->income_amount !== null)
                            <span style="display:flex;align-items:center;gap:.35rem;font-size:.875rem">
                                @if($booking->income_tax)
                                    <span style="color:#2e7d32;font-size:1rem">✓</span>
                                @else
                                    <span style="color:#9e9e9e;font-size:1rem">✗</span>
                                @endif
                                Incasso Ricevuto
                                <span style="font-size:.75rem;color:#6b7f89">(€&nbsp;{{ number_format((float)$booking->income_amount, 2, ',', '.') }})</span>
                            </span>
                        @endif
                        @if($booking->cleaning_amount !== null)
                            <span style="display:flex;align-items:center;gap:.35rem;font-size:.875rem">
                                @if($booking->cleaning_tax)
                                    <span style="color:#2e7d32;font-size:1rem">✓</span>
                                @else
                                    <span style="color:#9e9e9e;font-size:1rem">✗</span>
                                @endif
                                Pulizie
                                <span style="font-size:.75rem;color:#6b7f89">(€&nbsp;{{ number_format((float)$booking->cleaning_amount, 2, ',', '.') }})</span>
                            </span>
                        @endif
                        @if($booking->linen_amount !== null)
                            <span style="display:flex;align-items:center;gap:.35rem;font-size:.875rem">
                                @if($booking->linen_tax)
                                    <span style="color:#2e7d32;font-size:1rem">✓</span>
                                @else
                                    <span style="color:#9e9e9e;font-size:1rem">✗</span>
                                @endif
                                Biancheria
                                <span style="font-size:.75rem;color:#6b7f89">(€&nbsp;{{ number_format((float)$booking->linen_amount, 2, ',', '.') }})</span>
                            </span>
                        @endif
                        @if($booking->parking_amount !== null)
                            <span style="display:flex;align-items:center;gap:.35rem;font-size:.875rem">
                                @if($booking->parking_tax)
                                    <span style="color:#2e7d32;font-size:1rem">✓</span>
                                @else
                                    <span style="color:#9e9e9e;font-size:1rem">✗</span>
                                @endif
                                Posto auto
                                <span style="font-size:.75rem;color:#6b7f89">(€&nbsp;{{ number_format((float)$booking->parking_amount, 2, ',', '.') }})</span>
                            </span>
                        @endif
                    </div>
                </div>
            @endif
        @endif

        {{-- ── Segnalazione Ospiti (Guest Reporting) ── --}}
        @if(auth()->user()->hasPermission('manage_bookings'))
            <div class="a-card" style="margin-top:1.25rem">
                <div class="a-card__title">Segnalazione Ospiti</div>
                <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
                    <a href="{{ route('admin.guest-reporting.show', $booking) }}"
                       class="btn btn--outline btn--sm">📤 Segnala ospiti</a>

                    @php $lastReport = $booking->guestReports()->latest('submitted_at')->first(); @endphp
                    @if ($lastReport)
                        <span style="font-size:.8rem;color:#6b7f89">
                            Ultimo invio: {{ $lastReport->submitted_at->format('d/m/Y H:i') }}
                            &nbsp;
                            @if ($lastReport->mode === 'test')
                                <span class="badge badge--outline" style="font-size:.7rem">Test</span>
                            @else
                                <span class="badge badge--primary" style="font-size:.7rem">Invio</span>
                            @endif
                            &nbsp;
                            @if ($lastReport->status === 'success')
                                <span class="badge badge--success" style="font-size:.7rem">Successo</span>
                            @else
                                <span class="badge badge--error" style="font-size:.7rem">Errore</span>
                            @endif
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </div>

@endsection

@push('scripts')
<script>
(function () {
    document.querySelectorAll('.js-confirm-send-mail').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const message = form.dataset.confirmMessage;
            if (message && !window.confirm(message)) {
                e.preventDefault();
            }
        });
    });
})();
</script>
<script>
(function () {
    const btn = document.getElementById('btn-telegram-notify');
    if (!btn) return;

    const toast = document.getElementById('telegram-toast');

    function showToast(message, success) {
        toast.textContent = message;
        toast.style.display = 'block';
        toast.style.padding = '.6rem 1rem';
        toast.style.borderRadius = '6px';
        toast.style.fontSize = '.875rem';
        toast.style.background = success ? '#d1fae5' : '#fee2e2';
        toast.style.color = success ? '#065f46' : '#991b1b';
        toast.style.border = '1px solid ' + (success ? '#6ee7b7' : '#fca5a5');
        setTimeout(() => { toast.style.display = 'none'; }, 5000);
    }

    btn.addEventListener('click', function () {
        if (!window.confirm('Inviare la notifica Telegram della prenotazione a tutti i destinatari configurati?')) {
            return;
        }

        btn.disabled = true;

        fetch(btn.dataset.url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.sent) {
                showToast('✓ Notifica Telegram inviata.', true);
            } else if (data.reason === 'no_recipients') {
                showToast('Nessun destinatario configurato. Aggiungi un Telegram Chat ID negli utenti.', false);
            } else {
                showToast('Invio completato (controlla i log per dettagli).', true);
            }
        })
        .catch(() => {
            showToast('Errore di rete durante l\'invio.', false);
        })
        .finally(() => {
            btn.disabled = false;
        });
    });
})();
</script>
@endpush
