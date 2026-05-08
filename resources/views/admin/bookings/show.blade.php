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
                <form method="POST" action="{{ route('admin.bookings.destroy', $booking) }}"
                      onsubmit="return confirm('Eliminare questa prenotazione?')" style="margin-left:auto">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn--danger btn--sm">Elimina</button>
                </form>
            @endif
        </div>

        @if(session('success'))
            <div class="flash flash--success" style="margin-bottom:.75rem">{{ session('success') }}</div>
        @endif

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
                                      title="{{ $booking->income_paid ? 'Pagato' : 'Da pagare' }}">
                                    € {{ number_format((float)$booking->income_amount, 2, ',', '.') }}
                                </span>
                                <span style="font-size:.8rem;color:#6b7f89;margin-left:.35rem">
                                    {{ $booking->income_paid ? 'Pagato' : 'Da pagare' }}
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
                            <th>Totale uscite</th>
                            <td>€ {{ number_format($booking->total_expenses, 2, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if ($booking->income_amount !== null && $booking->total_expenses !== null)
                        <tr>
                            <th>Saldo netto</th>
                            <td>
                                @php $net = (float)$booking->income_amount + (float)($booking->parking_amount ?? 0) - $booking->total_expenses; @endphp
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
                                Incasso
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
    </div>
@endsection

@push('scripts')
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
