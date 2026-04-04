@extends('layouts.admin')

@section('title', 'Prenotazioni')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h1 style="font-size:1.1rem;font-weight:700">Prenotazioni</h1>
        <a href="{{ route('admin.bookings.create') }}" class="btn btn--primary">+ Nuova prenotazione</a>
    </div>

    <div class="a-card">
        @if ($bookings->isEmpty())
            <p style="color:#6b7f89;font-size:.875rem">Nessuna prenotazione registrata.</p>
        @else
            <table class="a-table">
                <thead>
                    <tr>
                        <th>Ospite</th>
                        <th>Stato</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Notti</th>
                        <th>Ospiti</th>
                        <th>Origine</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bookings as $booking)
                        <tr @if($booking->isCanceled()) style="opacity:.72" @endif>
                            <td>
                                <a href="{{ route('admin.bookings.show', $booking) }}"
                                   style="color:#30596C;font-weight:600;text-decoration:none">
                                    {{ $booking->person->full_name }}
                                </a>
                            </td>
                            <td>
                                @if($booking->isCanceled())
                                    <span class="badge badge--canceled">cancellata</span>
                                @else
                                    <span class="badge badge--booked">attiva</span>
                                @endif
                            </td>
                            <td>{{ $booking->checkin->format('d/m/Y') }}</td>
                            <td>{{ $booking->checkout->format('d/m/Y') }}</td>
                            <td>{{ $booking->nights }}</td>
                            <td>
                                {{ $booking->total_guests }}
                                @if (($booking->babies ?? 0) > 0)
                                    <span title="{{ $booking->babies }} neonato/i" aria-label="{{ $booking->babies }} neonato/i" style="font-size:.85em">👶</span>
                                @endif
                                @if ($booking->pets > 0)
                                    <span title="{{ $booking->pets }} animale/i" aria-label="{{ $booking->pets }} animale/i" style="font-size:.85em">🐾</span>
                                @endif
                            </td>
                            <td><span class="badge badge--{{ $booking->source }}">{{ $booking->source }}</span></td>
                            <td style="white-space:nowrap">
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn--outline btn--sm">Vedi</a>
                                <a href="{{ route('admin.bookings.edit', $booking) }}" class="btn btn--outline btn--sm">Modifica</a>
                                <form method="POST" action="{{ route('admin.bookings.destroy', $booking) }}"
                                      style="display:inline" onsubmit="return confirm('Eliminare la prenotazione?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn--danger btn--sm">Elimina</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="pagination-wrap">
                {{ $bookings->links() }}
            </div>
        @endif
    </div>
@endsection
