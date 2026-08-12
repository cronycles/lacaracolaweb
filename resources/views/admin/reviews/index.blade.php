@extends('layouts.admin')

@section('title', 'Recensioni')

@section('content')

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h1 style="font-size:1.1rem;font-weight:700">Recensioni</h1>
    </div>

    <div class="a-card">
        @if($bookings->isEmpty())
            <p style="color:#6b7f89;font-size:.875rem">Nessuna prenotazione trovata.</p>
        @else
            <table class="a-table">
                <thead>
                    <tr>
                        <th>Ospite</th>
                        <th>Checkout</th>
                        <th>Fonte</th>
                        <th>Recensione</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bookings as $booking)
                        <tr>
                            <td style="font-weight:600">
                                {{ $booking->person?->full_name ?? '—' }}
                            </td>
                            <td>{{ $booking->checkout->format('d/m/Y') }}</td>
                            <td>{{ $booking->source ?? '—' }}</td>
                            <td>
                                @if($booking->review)
                                    <span style="color:#15803d;font-size:.8rem;font-weight:600">
                                        ✓ {{ $booking->review->rating }} / 10
                                        @unless($booking->review->is_active)
                                            <span style="color:#6b7f89">(nascosta)</span>
                                        @endunless
                                    </span>
                                @else
                                    <span style="color:#9ca3af;font-size:.8rem">—</span>
                                @endif
                            </td>
                            <td style="white-space:nowrap">
                                @if($booking->review)
                                    <a href="{{ route('admin.reviews.edit', $booking->review) }}" class="btn btn--outline btn--sm">Modifica</a>
                                    <form method="POST" action="{{ route('admin.reviews.destroy', $booking->review) }}" style="display:inline"
                                          onsubmit="return confirm('Eliminare questa recensione?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn--outline btn--sm" style="color:#c62828">Elimina</button>
                                    </form>
                                @else
                                    <a href="{{ route('admin.reviews.create', $booking) }}" class="btn btn--primary btn--sm">+ Aggiungi</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

@endsection
