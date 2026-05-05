@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')

    {{-- ═══════════════════════════════════════════════
         SEZIONE 1 — KPI primari
         ═══════════════════════════════════════════════ --}}

    {{-- 1a. Saldo totale (solo contabilità) --}}
    @if(auth()->user()->hasPermission('view_accounting'))
    <div class="stats-grid stats-grid--hero" style="margin-bottom:1.25rem">
        <div class="stat-card stat-card--hero" style="border-left:4px solid {{ $stats['global_balance'] >= 0 ? '#7b1fa2' : '#c62828' }}">
            <div class="stat-card__number" style="color:{{ $stats['global_balance'] >= 0 ? '#7b1fa2' : '#c62828' }}">
                € {{ number_format($stats['global_balance'], 2, ',', '.') }}
            </div>
            <div class="stat-card__label">Saldo totale</div>
        </div>

        {{-- 1b. Pulizie e biancheria da pagare (accanto al saldo) --}}
        @if(auth()->user()->hasPermission('view_bookings'))
        <div class="stat-card stat-card--hero" style="border-left:4px solid #92400e">
            <div class="stat-card__number" style="color:#92400e">
                € {{ number_format($stats['cleaning_unpaid'], 2, ',', '.') }}
            </div>
            <div class="stat-card__label">Pulizie da pagare</div>
        </div>
        <div class="stat-card stat-card--hero" style="border-left:4px solid #92400e">
            <div class="stat-card__number" style="color:#92400e">
                € {{ number_format($stats['linen_unpaid'], 2, ',', '.') }}
            </div>
            <div class="stat-card__label">Biancheria da pagare</div>
        </div>
        @endif
    </div>
    @elseif(auth()->user()->hasPermission('view_bookings'))
    {{-- Solo pulizie/biancheria (host_keeper) --}}
    <div class="stats-grid stats-grid--hero" style="margin-bottom:1.25rem">
        <div class="stat-card stat-card--hero" style="border-left:4px solid #92400e">
            <div class="stat-card__number" style="color:#92400e">
                € {{ number_format($stats['cleaning_unpaid'], 2, ',', '.') }}
            </div>
            <div class="stat-card__label">Pulizie da pagare</div>
        </div>
        <div class="stat-card stat-card--hero" style="border-left:4px solid #92400e">
            <div class="stat-card__number" style="color:#92400e">
                € {{ number_format($stats['linen_unpaid'], 2, ',', '.') }}
            </div>
            <div class="stat-card__label">Biancheria da pagare</div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════
         SEZIONE 2 — Soggiorno in corso
         ═══════════════════════════════════════════════ --}}
    @if($stats['current_booking'])
    <div class="dashboard-banner">
        <div class="dashboard-banner__icon">🏠</div>
        <div class="dashboard-banner__body">
            <div class="dashboard-banner__title">{{ $stats['current_booking']->person->full_name }} è in casa</div>
            <div class="dashboard-banner__meta">
                Check-in {{ $stats['current_booking']->checkin->format('d/m/Y') }}
                &nbsp;→&nbsp;
                Check-out {{ $stats['current_booking']->checkout->format('d/m/Y') }}
                ({{ $stats['current_booking']->nights }} notti)
            </div>
        </div>
        <a href="{{ route('admin.bookings.show', $stats['current_booking']) }}" class="dashboard-banner__action">
            Dettaglio
        </a>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════
         SEZIONE 3 — Prossimi arrivi
         ═══════════════════════════════════════════════ --}}
    @if(auth()->user()->hasPermission('view_bookings') && $stats['upcoming']->isNotEmpty())
    <div class="a-card">
        <div class="a-card__title">Prossimi arrivi</div>
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
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════
         SEZIONE 4 — Contabilità anno corrente (secondaria)
         Solo view_accounting
         ═══════════════════════════════════════════════ --}}
    @if(auth()->user()->hasPermission('view_accounting'))
    <div style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#9ca8b0;margin:1.75rem 0 .5rem">
        Contabilità {{ $stats['finance_year'] }}
    </div>
    <div class="stats-grid stats-grid--secondary">
        <div class="stat-card stat-card--secondary" style="border-left:3px solid #2e7d32">
            <div class="stat-card__number" style="color:#2e7d32">
                € {{ number_format($stats['total_income'], 2, ',', '.') }}
            </div>
            <div class="stat-card__label">Ingressi {{ $stats['finance_year'] }}</div>
        </div>
        <div class="stat-card stat-card--secondary" style="border-left:3px solid #c62828">
            <div class="stat-card__number" style="color:#c62828">
                € {{ number_format($stats['total_expenses'], 2, ',', '.') }}
            </div>
            <div class="stat-card__label">Uscite {{ $stats['finance_year'] }}</div>
        </div>
        <div class="stat-card stat-card--secondary" style="border-left:3px solid {{ $stats['balance'] >= 0 ? '#1976d2' : '#c62828' }}">
            <div class="stat-card__number" style="color:{{ $stats['balance'] >= 0 ? '#1976d2' : '#c62828' }}">
                € {{ number_format($stats['balance'], 2, ',', '.') }}
            </div>
            <div class="stat-card__label">Saldo {{ $stats['finance_year'] }}</div>
        </div>
        <div class="stat-card stat-card--secondary" style="border-left:3px solid #166534">
            <div class="stat-card__number" style="color:#166534">
                € {{ number_format($stats['cleaning_paid_total'], 2, ',', '.') }}
            </div>
            <div class="stat-card__label">Pulizie pagate (tot.)</div>
        </div>
        <div class="stat-card stat-card--secondary" style="border-left:3px solid #166534">
            <div class="stat-card__number" style="color:#166534">
                € {{ number_format($stats['linen_paid_total'], 2, ',', '.') }}
            </div>
            <div class="stat-card__label">Biancheria pagata (tot.)</div>
        </div>
    </div>
    <div style="margin-bottom:1.5rem">
        <a href="{{ route('admin.finance.index') }}" class="btn btn--outline btn--sm">📒 Vedi contabilità completa</a>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════
         SEZIONE 5 — Statistiche marginali
         ═══════════════════════════════════════════════ --}}
    <div style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#9ca8b0;margin:1.75rem 0 .5rem">
        Statistiche generali
    </div>
    <div class="stats-grid stats-grid--marginal">
        <div class="stat-card stat-card--marginal">
            <div class="stat-card__number">{{ $stats['active_bookings'] }}</div>
            <div class="stat-card__label">Prenotazioni attive</div>
        </div>
        @if(auth()->user()->hasPermission('view_accounting'))
        <div class="stat-card stat-card--marginal">
            <div class="stat-card__number">{{ $stats['total_bookings'] }}</div>
            <div class="stat-card__label">Prenotazioni storico</div>
        </div>
        <div class="stat-card stat-card--marginal">
            <div class="stat-card__number">{{ $stats['canceled_bookings'] }}</div>
            <div class="stat-card__label">Cancellate</div>
        </div>
        <div class="stat-card stat-card--marginal">
            <div class="stat-card__number">{{ $stats['total_guests'] }}</div>
            <div class="stat-card__label">Ospiti registrati</div>
        </div>
        <div class="stat-card stat-card--marginal">
            <div class="stat-card__number">{{ $stats['newsletter_subs'] }}</div>
            <div class="stat-card__label">Newsletter</div>
        </div>
        @endif
    </div>

@endsection

