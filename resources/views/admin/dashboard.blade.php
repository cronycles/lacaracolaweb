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

    {{-- Financial KPIs --}}
    <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#6b7f89;margin:1.5rem 0 .6rem">
        Contabilità {{ $stats['finance_year'] }}
    </div>
    <div class="stats-grid" style="margin-bottom:1.5rem">
        <div class="stat-card" style="border-left:4px solid #2e7d32">
            <div class="stat-card__number" style="color:#2e7d32">
                € {{ number_format($stats['total_income'], 2, ',', '.') }}
            </div>
            <div class="stat-card__label">Ingressi {{ $stats['finance_year'] }}</div>
        </div>
        <div class="stat-card" style="border-left:4px solid #c62828">
            <div class="stat-card__number" style="color:#c62828">
                € {{ number_format($stats['total_expenses'], 2, ',', '.') }}
            </div>
            <div class="stat-card__label">Uscite {{ $stats['finance_year'] }}</div>
        </div>
        <div class="stat-card" style="border-left:4px solid {{ $stats['balance'] >= 0 ? '#1976d2' : '#c62828' }}">
            <div class="stat-card__number" style="color:{{ $stats['balance'] >= 0 ? '#1976d2' : '#c62828' }}">
                € {{ number_format($stats['balance'], 2, ',', '.') }}
            </div>
            <div class="stat-card__label">Saldo {{ $stats['finance_year'] }}</div>
        </div>
    </div>
    <div style="margin-bottom:1.5rem">
        <a href="{{ route('admin.finance.index') }}" class="btn btn--outline btn--sm">
            📒 Vedi contabilità completa
        </a>
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
