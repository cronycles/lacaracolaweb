@extends('layouts.admin')

@section('title', 'Prenotazione — ' . $booking->person->full_name)

@section('content')
    <div style="max-width:680px">
        <div style="display:flex;gap:.75rem;margin-bottom:1rem;align-items:center">
            <a href="{{ route('admin.bookings.index') }}" class="btn btn--outline btn--sm">← Prenotazioni</a>
            <a href="{{ route('admin.bookings.edit', $booking) }}" class="btn btn--primary btn--sm">Modifica</a>
            <form method="POST" action="{{ route('admin.bookings.destroy', $booking) }}"
                  onsubmit="return confirm('Eliminare questa prenotazione?')" style="margin-left:auto">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn--danger btn--sm">Elimina</button>
            </form>
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
    </div>
@endsection
