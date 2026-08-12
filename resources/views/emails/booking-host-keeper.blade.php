@extends('emails.layout')

@section('title', 'Prenotazione confermata — La Caracola')

@section('content')

    @php
        $cleaningAmount = (float) ($booking->cleaning_amount ?? 0);
        $linenAmount = (float) ($booking->linen_amount ?? 0);
        $serviceTotal = $cleaningAmount + $linenAmount;
        $ownerAddress = implode(', ', array_filter([
            $paymentOwner?->address_street,
            trim(implode(' ', array_filter([$paymentOwner?->address_zip, $paymentOwner?->address_city]))),
        ]));
        $ownerName = $paymentOwner?->payment_beneficiary ?: $paymentOwner?->name;
    @endphp

    <h1>🐚 Prenotazione confermata — La Caracola</h1>

    <p>È stata confermata una nuova prenotazione. Di seguito il riepilogo operativo per la casa.</p>

    <div class="section">
        <div class="section-title">Riepilogo del soggiorno</div>
        <table>
            <tr>
                <th>Nome prenotazione</th>
                <td>{{ $booking->person->full_name }}</td>
            </tr>
            @if ($booking->person->phone_display)
            <tr>
                <th>Telefono ospite</th>
                <td><a href="tel:{{ $booking->person->phone_display }}">{{ $booking->person->phone_display }}</a></td>
            </tr>
            @endif
            @if ($booking->person->email)
            <tr>
                <th>Email ospite</th>
                <td><a href="mailto:{{ $booking->person->email }}">{{ $booking->person->email }}</a></td>
            </tr>
            @endif
            <tr>
                <th>Check-in</th>
                <td>{{ $booking->checkin->translatedFormat('d F Y') }} (dalle {{ config('apartment.booking.checkin_time') }})</td>
            </tr>
            <tr>
                <th>Check-out</th>
                <td>{{ $booking->checkout->translatedFormat('d F Y') }} (entro le {{ config('apartment.booking.checkout_time') }})</td>
            </tr>
            <tr>
                <th>Adulti</th>
                <td>{{ $booking->adults }}</td>
            </tr>
            <tr>
                <th>Bambini</th>
                <td>{{ $booking->children }}</td>
            </tr>
            <tr>
                <th>Neonati</th>
                <td>{{ $booking->babies }}</td>
            </tr>
            <tr>
                <th>Animali domestici</th>
                <td>{{ $booking->pets }}</td>
            </tr>
            <tr>
                <th>Parcheggio privato</th>
                <td>{{ $booking->parking_amount !== null ? 'Sì' : 'No' }}</td>
            </tr>
            @if ($booking->parking_amount !== null)
            <tr>
                <th>Costo parcheggio</th>
                <td>€ {{ number_format((float) $booking->parking_amount, 2, ',', '.') }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">Servizi per la casa</div>
        <table>
            <tr>
                <th>Costo totale servizio</th>
                <td><strong>€ {{ number_format($serviceTotal, 2, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <th>Dettaglio</th>
                <td>Pulizie € {{ number_format($cleaningAmount, 2, ',', '.') }}; biancheria € {{ number_format($linenAmount, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    @if ($ownerName || $paymentOwner?->tax_code || $ownerAddress)
    <div class="payment-section">
        <div class="payment-title">Dati per la ricevuta fiscale della biancheria</div>
        @if ($ownerName)
            <strong>Nome e Cognome:</strong> {{ $ownerName }}<br>
        @endif
        @if ($paymentOwner?->tax_code)
            <strong>Codice fiscale:</strong> {{ $paymentOwner->tax_code }}<br>
        @endif
        @if ($ownerAddress)
            <strong>Indirizzo:</strong> {{ $ownerAddress }}
        @endif
    </div>
    @endif

    <div class="payment-section">
        <div class="payment-title">Dati per la ricevuta di incasso</div>
        <p><strong>Committente:</strong> {{ $ownerName }}@if($paymentOwner?->tax_code) — C.F. {{ $paymentOwner->tax_code }}@endif</p>
        <table>
            <tr>
                <th>Compenso per servizio occasionale di check-in, check-out e pulizia immobile per il soggiorno dal {{ $booking->checkin->format('d/m/Y') }} al {{ $booking->checkout->format('d/m/Y') }}</th>
                <td>€ {{ number_format($cleaningAmount, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Rimborso spese in nome e per conto del committente per fornitura biancheria</th>
                <td>€ {{ number_format($linenAmount, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Corrispettivo lordo soggetto a tassazione</th>
                <td>€ {{ number_format($cleaningAmount, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Rimborso spese non imponibile (Art. 15 DPR 633/72)</th>
                <td>€ {{ number_format($linenAmount, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <th><strong>Totale incassato tramite bonifico</strong></th>
                <td><strong>€ {{ number_format($serviceTotal, 2, ',', '.') }}</strong></td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Riepilogo operativo inviato da <a href="https://lacaracolaandora.com">lacaracolaandora.com</a>.
    </div>

@endsection
