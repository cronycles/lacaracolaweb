@extends('layouts.admin')

@section('title', 'Formula di calcolo prezzi')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;gap:.75rem;flex-wrap:wrap">
        <h1 style="font-size:1.1rem;font-weight:700">Formula di calcolo prezzi</h1>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <a href="{{ route('admin.pricing.portal-prices') }}" class="btn btn--outline">Prezzi portali</a>
            <a href="{{ route('admin.pricing.index') }}" class="btn btn--outline">← Prezzi base</a>
        </div>
    </div>

    <div class="a-card" style="max-width:720px">
        <div class="a-card__title">Modello di Ammortamento Lineare + Sconto Soggiorno + Fiscalità</div>
        <p style="font-size:.9rem;color:#374151;margin-bottom:1rem;line-height:1.6">
            Il calo del prezzo medio per notte avviene in parte naturalmente, perché i
            <em>costi fissi reali</em> si spalmano su più notti, e in parte per un vero sconto
            percentuale applicato alla tariffa notte per soggiorni lunghi (configurabile in
            <a href="{{ route('admin.settings') }}" style="color:var(--admin-accent)">Impostazioni → Fiscalità e prezzi</a>).
        </p>

        <h2 style="font-size:.9rem;font-weight:700;margin-bottom:.5rem;color:#1f2937">Formula</h2>
        <div style="background:#f3f4f6;border-radius:.375rem;padding:.75rem 1rem;font-family:monospace;font-size:.88rem;line-height:1.8;margin-bottom:1.25rem">
            Soggiorno Lordo = Σ(tariffa notte i)<br>
            Sconto Soggiorno = Soggiorno Lordo × (20% se ≥28 notti, 10% se ≥7 notti, altrimenti 0%)<br>
            Soggiorno Netto = Soggiorno Lordo − Sconto Soggiorno<br>
            Maggiorazione Fiscale = Aliquota Fiscale × (Pulizie + Biancheria, se selezionate)<br>
            Prezzo Totale = arrotonda_5€(Soggiorno Netto + Maggiorazione Fiscale + Pulizie + Biancheria)<br>
            Prezzo Medio / Notte = Prezzo Totale ÷ Numero Notti
        </div>

        <p style="font-size:.85rem;color:#374151;margin-bottom:1.25rem;line-height:1.6">
            Lo sconto mensile (28+ notti) sostituisce, non si somma, allo sconto settimanale (7+
            notti). L'eventuale scarto di arrotondamento ai 5€ viene assorbito dalla tariffa
            notte. Il prezzo suggerito per Airbnb, Booking.com e HomeToGo (mostrato nel
            <a href="{{ route('admin.pricing.index') }}" style="color:var(--admin-accent)">simulatore prezzi</a>)
            si ottiene dividendo il prezzo diretto per <code>(1 − sconto soggiorno) × (1 − commissione portale)</code>,
            assumendo lo stesso sconto soggiorno configurato anche sul portale.
        </p>

        <h2 style="font-size:.9rem;font-weight:700;margin-bottom:.5rem;color:#1f2937">Costi Fissi (calcolati una volta per prenotazione)</h2>
        <table class="a-table" style="margin-bottom:1.25rem">
            <thead>
                <tr>
                    <th>Voce</th>
                    <th>Tipo</th>
                    <th>Importo</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Pulizie / Check-in / Check-out</td>
                    <td>Fisso</td>
                    <td>{{ number_format(config('apartment.booking.cleaning_fee', 0), 2, ',', '.') }} €</td>
                </tr>
                <tr>
                    <td>Biancheria</td>
                    <td>{{ number_format(config('apartment.booking.linen_fee_per_person', 0), 2, ',', '.') }} € × ospiti</td>
                    <td>Dinamico</td>
                </tr>
            </tbody>
        </table>

        <h2 style="font-size:.9rem;font-weight:700;margin-bottom:.5rem;color:#1f2937">Vincoli di soggiorno</h2>
        <table class="a-table" style="margin-bottom:1.25rem">
            <thead>
                <tr><th>Parametro</th><th>Valore</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Notti minime</td>
                    <td>{{ config('apartment.booking.min_nights', 3) }} notti</td>
                </tr>
                <tr>
                    <td>Notti massime</td>
                    <td>{{ config('apartment.booking.max_nights', 28) }} notti</td>
                </tr>
            </tbody>
        </table>

        <h2 style="font-size:.9rem;font-weight:700;margin-bottom:.5rem;color:#1f2937">Esempi (Settembre, 2 ospiti — tariffa 105€/notte)</h2>
        <table class="a-table">
            <thead>
                <tr>
                    <th>Notti</th>
                    <th>Soggiorno</th>
                    <th>Sconto</th>
                    <th>Costi fissi</th>
                    <th>Fiscalità</th>
                    <th>Totale</th>
                    <th>Media/notte</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $nightRate = 105;
                    $fixed = config('apartment.booking.cleaning_fee', 100)
                           + config('apartment.booking.linen_fee_per_person', 25) * 2;
                    $taxRate = (float) \App\Models\Setting::get('pricing_tax_rate', '0.21');
                    $weeklyRate = (float) \App\Models\Setting::get('pricing_weekly_discount_percent', '0.10');
                    $monthlyRate = (float) \App\Models\Setting::get('pricing_monthly_discount_percent', '0.20');
                    foreach ([3, 5, 7, 10, 14] as $n) {
                        $stayGross = $nightRate * $n;
                        $discountRate = $n >= 28 ? $monthlyRate : ($n >= 7 ? $weeklyRate : 0);
                        $discount = round($stayGross * $discountRate);
                        $stayNet = $stayGross - $discount;
                        $tax = round($fixed * $taxRate);
                        $total = round(($stayNet + $tax + $fixed) / 5) * 5;
                        $avg = $total / $n;
                        echo "<tr>
                            <td>{$n}</td>
                            <td>" . number_format($stayGross, 2, ',', '.') . " €</td>
                            <td>" . ($discount > 0 ? '−' . number_format($discount, 2, ',', '.') . ' €' : '—') . "</td>
                            <td>" . number_format($fixed, 2, ',', '.') . " €</td>
                            <td>+" . number_format($tax, 2, ',', '.') . " €</td>
                            <td style='font-weight:600'>" . number_format($total, 2, ',', '.') . " €</td>
                            <td>" . number_format($avg, 2, ',', '.') . " €</td>
                        </tr>";
                    }
                @endphp
            </tbody>
        </table>

        <p style="font-size:.8rem;color:#6b7f89;margin-top:1rem">
            Le tariffe notte effettive si configurano nella pagina
            <a href="{{ route('admin.pricing.index') }}" style="color:var(--admin-accent)">Prezzi base →</a>
        </p>
    </div>
@endsection
