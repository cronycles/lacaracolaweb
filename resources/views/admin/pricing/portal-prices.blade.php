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
        <div class="a-card__title">Legenda: cosa impostare su ogni portale</div>

        <ul style="font-size:.85rem;color:#374151;line-height:1.7;margin:0 0 1rem 1.1rem;padding:0">
            <li>
                <strong>Tariffa base (colonne della tabella):</strong> già comprensiva di biancheria per 2 ospiti
                e maggiorazione fiscale, al netto della commissione del portale — nessun'altra voce da aggiungere
                per la tariffa notte.
            </li>
            <li>
                <strong>Pulizie fisse:</strong> {{ number_format($cleaningFeeCents / 100, 0, ',', '.') }} € a
                prenotazione, da inserire così com'è nel campo "costo pulizie" di ogni portale — nessun calcolo,
                nessuna maggiorazione.
            </li>
            <li>
                <strong>Supplemento ospite extra:</strong> {{ number_format($extraGuestFeeCents / 100, 0, ',', '.') }} €
                a notte, per persona, dal 3° ospite in poi — stesso valore identico su Airbnb, Booking.com e HomeToGo.
            </li>
            <li>
                <strong>Airbnb / Booking.com:</strong> non offrono un campo biancheria per ospite — non serve, è già
                inclusa nella tariffa base.
            </li>
            <li>
                <strong>HomeToGo:</strong> offre un campo biancheria per ospite, lasciato volutamente non impostato
                per mantenere i 3 portali configurati allo stesso modo.
            </li>
            <li>
                <strong>Nota sulle pulizie:</strong> essendo un importo fisso non maggiorato per la commissione del
                portale, ogni portale trattiene circa pulizie × commissione (≈15–16 € con le commissioni attuali)
                invece di girarlo interamente all'host — scelta accettata consapevolmente, rivedibile in futuro.
            </li>
        </ul>

        <p style="font-size:.78rem;color:#6b7f89;margin:0">
            Tutti questi valori sono modificabili in
            <a href="{{ route('admin.settings') }}" style="color:var(--admin-accent)">Impostazioni → Fiscalità e prezzi</a>.
        </p>
    </div>

    <div class="a-card" style="max-width:900px;margin-top:1.5rem">
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
