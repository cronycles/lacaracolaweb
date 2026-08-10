@extends('emails.layout')

@section('title', 'Prenotazione confermata — La Caracola')

@section('content')

    <h1>🐚 Prenotazione confermata — La Caracola</h1>

    <p>È stata confermata una nuova prenotazione. Di seguito il riepilogo operativo per la casa.</p>

    <div class="section">
        <div class="section-title">Riepilogo del soggiorno</div>
        <table>
            <tr>
                <th>Nome prenotazione</th>
                <td>{{ $booking->person->full_name }}</td>
            </tr>
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
                <td>€ {{ number_format((float) ($booking->cleaning_amount ?? 0) + (float) ($booking->linen_amount ?? 0), 2, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Dettaglio</th>
                <td>Biancheria € {{ number_format((float) ($booking->linen_amount ?? 0), 2, ',', '.') }}; pulizie € {{ number_format((float) ($booking->cleaning_amount ?? 0), 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="callout">
        La ricevuta deve essere intestata a <strong>Marco Crosetti</strong><br>
        Codice fiscale: <strong>CRSMRC60D24D969K</strong>
    </div>

    <div class="footer">
        Riepilogo operativo inviato da <a href="https://lacaracolaandora.com">lacaracolaandora.com</a>.
    </div>

@endsection
