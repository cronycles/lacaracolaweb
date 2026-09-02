@extends('layouts.admin')

@section('title', 'Prezzi portali')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;gap:.75rem;flex-wrap:wrap">
        <h1 style="font-size:1.1rem;font-weight:700">Prezzi portali</h1>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <a href="{{ route('admin.stay-discounts.index') }}" class="btn btn--outline">Formula prezzi</a>
            <a href="{{ route('admin.pricing.index') }}" class="btn btn--outline">← Prezzi base</a>
        </div>
    </div>

    <div class="a-card" style="max-width:900px">
        <p style="font-size:.85rem;color:#374151;margin-bottom:1rem;line-height:1.6">
            Tariffa notte suggerita da impostare sul calendario di ciascun portale per lo stesso periodo,
            già comprensiva di pulizie, biancheria e maggiorazione fiscale (nessuna voce separata da esporre
            sul portale). Calcolata su un soggiorno e un numero di ospiti di riferimento, configurabili in
            <a href="{{ route('admin.settings') }}" style="color:var(--admin-accent)">Impostazioni → Fiscalità e prezzi</a>.
        </p>

        <table class="a-table">
            <thead>
                <tr>
                    <th>Periodo</th>
                    <th>Il tuo prezzo</th>
                    <th>Airbnb</th>
                    <th>Booking.com</th>
                    <th>HomeToGo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rules as $rule)
                    <tr>
                        <td>{{ $rule->period_label }}</td>
                        <td>{{ number_format($rule->price_euros, 2, ',', '.') }} €</td>
                        <td>{{ number_format($portalRates[$rule->id]['airbnb']['nightly_rate_cents'] / 100, 0, ',', '.') }} €</td>
                        <td>{{ number_format($portalRates[$rule->id]['booking']['nightly_rate_cents'] / 100, 0, ',', '.') }} €</td>
                        <td>{{ number_format($portalRates[$rule->id]['hometogo']['nightly_rate_cents'] / 100, 0, ',', '.') }} €</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Nessuna regola di prezzo configurata.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
