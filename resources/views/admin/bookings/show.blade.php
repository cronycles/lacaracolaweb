@extends('layouts.admin')

@section('title', 'Prenotazione — ' . $booking->person->full_name)

@section('content')
    <div style="max-width:680px">
        <div style="display:flex;gap:.75rem;margin-bottom:1rem;align-items:center">
            <a href="{{ route('admin.bookings.index') }}" class="btn btn--outline btn--sm">← Prenotazioni</a>
            @if(auth()->user()->hasPermission('manage_bookings'))
                <a href="{{ route('admin.bookings.edit', $booking) }}" class="btn btn--primary btn--sm">Modifica</a>
                <form method="POST" action="{{ route('admin.bookings.destroy', $booking) }}"
                      onsubmit="return confirm('Eliminare questa prenotazione?')" style="margin-left:auto">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn--danger btn--sm">Elimina</button>
                </form>
            @endif
        </div>

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
                        Le voci selezionate appariranno nella pagina
                        <a href="{{ route('admin.tax-declaration.index') }}" style="color:#30596C">Dichiarazione redditi</a>.
                        Solo le voci già incassate/pagate contribuiscono ai totali.
                    </p>

                    <form method="POST" action="{{ route('admin.bookings.update', $booking) }}">
                        @csrf
                        @method('PUT')

                        {{-- Pass through all required booking fields as hidden to satisfy validation --}}
                        <input type="hidden" name="person_id"    value="{{ $booking->person_id }}">
                        <input type="hidden" name="checkin"      value="{{ $booking->checkin->format('Y-m-d') }}">
                        <input type="hidden" name="checkout"     value="{{ $booking->checkout->format('Y-m-d') }}">
                        <input type="hidden" name="adults"       value="{{ $booking->adults }}">
                        <input type="hidden" name="children"     value="{{ $booking->children ?? 0 }}">
                        <input type="hidden" name="babies"       value="{{ $booking->babies ?? 0 }}">
                        @if($booking->pets !== null)
                        <input type="hidden" name="pets"         value="{{ $booking->pets }}">
                        @endif
                        <input type="hidden" name="source"       value="{{ $booking->source }}">
                        @if($booking->external_ref)
                        <input type="hidden" name="external_ref" value="{{ $booking->external_ref }}">
                        @endif
                        @if($booking->notes)
                        <input type="hidden" name="notes"        value="{{ $booking->notes }}">
                        @endif
                        <input type="hidden" name="income_paid"     value="{{ $booking->income_paid ? 1 : 0 }}">
                        @if($booking->income_paid_at)
                        <input type="hidden" name="income_paid_at"  value="{{ $booking->income_paid_at->format('Y-m-d') }}">
                        @endif
                        @if($booking->income_amount !== null)
                        <input type="hidden" name="income_amount"   value="{{ $booking->income_amount }}">
                        @endif
                        <input type="hidden" name="cleaning_paid"   value="{{ $booking->cleaning_paid ? 1 : 0 }}">
                        @if($booking->cleaning_amount !== null)
                        <input type="hidden" name="cleaning_amount" value="{{ $booking->cleaning_amount }}">
                        @endif
                        <input type="hidden" name="linen_paid"      value="{{ $booking->linen_paid ? 1 : 0 }}">
                        @if($booking->linen_amount !== null)
                        <input type="hidden" name="linen_amount"    value="{{ $booking->linen_amount }}">
                        @endif
                        <input type="hidden" name="parking_paid"    value="{{ $booking->parking_paid ? 1 : 0 }}">
                        @if($booking->parking_paid_at)
                        <input type="hidden" name="parking_paid_at" value="{{ $booking->parking_paid_at->format('Y-m-d') }}">
                        @endif
                        @if($booking->parking_amount !== null)
                        <input type="hidden" name="parking_amount"  value="{{ $booking->parking_amount }}">
                        @endif
                        @if($booking->services_paid_at)
                        <input type="hidden" name="services_paid_at" value="{{ $booking->services_paid_at->format('Y-m-d') }}">
                        @endif

                        <div style="display:flex;flex-wrap:wrap;gap:.75rem 1.5rem;margin-bottom:1rem">
                            @if($booking->income_amount !== null)
                                <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer">
                                    <input type="hidden"   name="income_tax" value="0">
                                    <input type="checkbox" name="income_tax" value="1" class="form-checkbox"
                                           @checked($booking->income_tax)>
                                    <span style="font-size:.9rem">Incasso</span>
                                    <span style="font-size:.75rem;color:#6b7f89">(€&nbsp;{{ number_format((float)$booking->income_amount, 2, ',', '.') }})</span>
                                </label>
                            @endif
                            @if($booking->cleaning_amount !== null)
                                <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer">
                                    <input type="hidden"   name="cleaning_tax" value="0">
                                    <input type="checkbox" name="cleaning_tax" value="1" class="form-checkbox"
                                           @checked($booking->cleaning_tax)>
                                    <span style="font-size:.9rem">Pulizie</span>
                                    <span style="font-size:.75rem;color:#6b7f89">(€&nbsp;{{ number_format((float)$booking->cleaning_amount, 2, ',', '.') }})</span>
                                </label>
                            @endif
                            @if($booking->linen_amount !== null)
                                <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer">
                                    <input type="hidden"   name="linen_tax" value="0">
                                    <input type="checkbox" name="linen_tax" value="1" class="form-checkbox"
                                           @checked($booking->linen_tax)>
                                    <span style="font-size:.9rem">Biancheria</span>
                                    <span style="font-size:.75rem;color:#6b7f89">(€&nbsp;{{ number_format((float)$booking->linen_amount, 2, ',', '.') }})</span>
                                </label>
                            @endif
                            @if($booking->parking_amount !== null)
                                <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer">
                                    <input type="hidden"   name="parking_tax" value="0">
                                    <input type="checkbox" name="parking_tax" value="1" class="form-checkbox"
                                           @checked($booking->parking_tax)>
                                    <span style="font-size:.9rem">Posto auto</span>
                                    <span style="font-size:.75rem;color:#6b7f89">(€&nbsp;{{ number_format((float)$booking->parking_amount, 2, ',', '.') }})</span>
                                </label>
                            @endif
                        </div>

                        <button type="submit" class="btn btn--primary btn--sm">Salva</button>
                    </form>
                </div>
            @endif
        @endif
    </div>
@endsection
