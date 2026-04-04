@extends('layouts.admin')

@section('title', 'Regole di prezzo')

@section('content')
    <div class="a-card" style="max-width:900px">
        <div class="a-card__title">Simulazione prezzo (privata)</div>

        <form id="pricing-sim-form"
              data-simulate-url="{{ route('admin.pricing.simulate') }}"
              data-locale="it-IT"
              data-min-nights="{{ config('apartment.booking.min_nights', 3) }}">
            <div class="date-picker" id="pricing-sim-date-picker"
                 data-locale="it"
                 data-min-nights="{{ config('apartment.booking.min_nights', 3) }}">
                <div class="date-picker__triggers">
                    <div class="date-picker__field">
                        <span class="date-picker__label">Check-in *</span>
                        <button type="button" id="pricing-sim-trigger-checkin" class="dp-trigger" aria-haspopup="true">
                            <span class="dp-trigger__icon" aria-hidden="true">🗓</span>
                            <span class="dp-trigger__value" data-placeholder="Seleziona arrivo">Seleziona arrivo</span>
                        </button>
                        <input type="hidden" id="sim-checkin" name="checkin">
                    </div>

                    <div class="date-picker__field">
                        <span class="date-picker__label">Check-out *</span>
                        <button type="button" id="pricing-sim-trigger-checkout" class="dp-trigger" aria-haspopup="true">
                            <span class="dp-trigger__icon" aria-hidden="true">🗓</span>
                            <span class="dp-trigger__value" data-placeholder="Seleziona partenza">Seleziona partenza</span>
                        </button>
                        <input type="hidden" id="sim-checkout" name="checkout">
                    </div>
                </div>

                <div id="pricing-sim-dp-popup" class="dp-popup" hidden role="dialog" aria-label="Calendar"></div>
            </div>
        </form>

        <div id="pricing-sim-result" style="margin-top:1rem;display:none;border:1px solid var(--admin-border);border-radius:.5rem;padding:.9rem;background:#f9fafb">
            <p style="font-size:.85rem;color:#6b7f89;margin-bottom:.35rem">Dettaglio simulazione</p>
            <p id="pricing-sim-summary" style="font-weight:600;margin-bottom:.4rem">—</p>
            <p id="pricing-sim-breakdown" style="font-size:.9rem;color:#374151">—</p>
        </div>

        <p id="pricing-sim-error" style="margin-top:.75rem;font-size:.85rem;color:#991b1b;display:none"></p>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;gap:.75rem;flex-wrap:wrap">
        <h1 style="font-size:1.1rem;font-weight:700">Regole di prezzo</h1>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <a href="{{ route('admin.stay-discounts.index') }}" class="btn btn--outline">Sconti soggiorno</a>
            <a href="{{ route('admin.pricing.create') }}" class="btn btn--primary">+ Nuova regola</a>
        </div>
    </div>

    <div class="a-card">
        @if ($rules->isEmpty())
            <p style="color:#6b7f89;font-size:.875rem">Nessuna regola di prezzo configurata.</p>
        @else
            <table class="a-table">
                <thead>
                    <tr>
                        <th>Periodo ricorrente</th>
                        <th>€ / notte</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rules as $rule)
                        <tr>
                            <td style="font-weight:600">{{ $rule->period_label }}</td>
                            <td>{{ number_format($rule->price_euros, 2, ',', '.') }} €</td>
                            <td style="white-space:nowrap">
                                <a href="{{ route('admin.pricing.edit', $rule) }}" class="btn btn--outline btn--sm">Modifica</a>
                                <form method="POST" action="{{ route('admin.pricing.destroy', $rule) }}"
                                      style="display:inline" onsubmit="return confirm('Eliminare questa regola?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn--danger btn--sm">Elimina</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

@endsection
