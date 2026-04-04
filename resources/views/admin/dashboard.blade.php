@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    {{-- KPI cards --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card__number">{{ $stats['total_bookings'] }}</div>
            <div class="stat-card__label">Prenotazioni totali (storico)</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__number">{{ $stats['active_bookings'] }}</div>
            <div class="stat-card__label">Prenotazioni attive</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__number">{{ $stats['canceled_bookings'] }}</div>
            <div class="stat-card__label">Prenotazioni cancellate</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__number">{{ $stats['total_guests'] }}</div>
            <div class="stat-card__label">Ospiti registrati</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__number">{{ $stats['newsletter_subs'] }}</div>
            <div class="stat-card__label">Iscritti newsletter</div>
        </div>
    </div>

    {{-- Upcoming bookings --}}
    <div class="a-card">
        <div class="a-card__title">Prossimi arrivi</div>

        @if ($stats['upcoming']->isEmpty())
            <p style="color:#6b7f89;font-size:.875rem">Nessuna prenotazione futura.</p>
        @else
            <table class="a-table">
                <thead>
                    <tr>
                        <th>Ospite</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Notti</th>
                        <th>Origine</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stats['upcoming'] as $booking)
                        <tr>
                            <td>
                                <a href="{{ route('admin.bookings.show', $booking) }}" style="color:#30596C;font-weight:600;text-decoration:none">
                                    {{ $booking->person->full_name }}
                                </a>
                            </td>
                            <td>{{ $booking->checkin->format('d/m/Y') }}</td>
                            <td>{{ $booking->checkout->format('d/m/Y') }}</td>
                            <td>{{ $booking->nights }}</td>
                            <td><span class="badge badge--{{ $booking->source }}">{{ $booking->source }}</span></td>
                            <td>
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn--outline btn--sm">Dettaglio</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Quick actions --}}
    <div class="a-card">
        <div class="a-card__title">Azioni rapide</div>
        <div style="display:flex;gap:.75rem;flex-wrap:wrap">
            <a href="{{ route('admin.bookings.create') }}" class="btn btn--primary">+ Nuova prenotazione</a>
            <a href="{{ route('admin.people.create') }}" class="btn btn--outline">+ Nuovo ospite</a>
            <a href="{{ route('admin.calendar') }}" class="btn btn--outline">Calendario disponibilità</a>
            <a href="{{ route('admin.pricing.create') }}" class="btn btn--outline">+ Regola prezzi</a>
        </div>
    </div>
@endsection
