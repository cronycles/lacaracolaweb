@extends('emails.layout')

@section('title', 'Pagamento ricevuto — La Caracola')

@section('content')

    <h1>🐚 Pagamento ricevuto correttamente</h1>

    <p>Gentile {{ $booking->person->first_name }}, abbiamo ricevuto correttamente il pagamento della tua prenotazione a La Caracola.</p>

    <div class="section">
        <div class="section-title">Riepilogo della prenotazione</div>
        <table>
            <tr>
                <th>Nome</th>
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
            @if ($booking->children > 0)
            <tr>
                <th>Bambini</th>
                <td>{{ $booking->children }}</td>
            </tr>
            @endif
            @if ($booking->babies > 0)
            <tr>
                <th>Neonati</th>
                <td>{{ $booking->babies }}</td>
            </tr>
            @endif
            @if ($booking->house_price !== null)
            <tr>
                <th>Prezzo totale casa</th>
                <td><strong>€ {{ number_format($booking->house_price, 2, ',', '.') }}</strong></td>
            </tr>
            @endif
            @if ($booking->parking_amount !== null)
            <tr>
                <th>Prezzo parcheggio</th>
                <td>€ {{ number_format((float) $booking->parking_amount, 2, ',', '.') }}<br>
                    <small>Da pagare in loco al momento dell'arrivo</small></td>
            </tr>
            @endif
        </table>
    </div>

    <div class="payment-section">
        <div class="payment-title">IMPORTANTE: completa il check-in online</div>
        Se non lo hai ancora fatto, compila il check-in prima del tuo arrivo. È necessario per prepararci ad accoglierti.
        <p><a href="{{ $checkinUrl }}" class="btn">Compila il check-in online</a></p>
    </div>

    <div class="section">
        <div class="section-title">Il tuo contatto per il tuo soggiorno</div>
        <p>Qualche giorno prima del tuo arrivo, il/la host keeper ti contatterà per organizzare il check-in. Per qualsiasi domanda puoi contattarlo/a direttamente:</p>
        @forelse ($hostKeepers as $hostKeeper)
            <p><strong>{{ $hostKeeper->name }}</strong><br>
                <a href="mailto:{{ $hostKeeper->email }}">{{ $hostKeeper->email }}</a>
                @if ($hostKeeper->phone)
                    <br><a href="tel:{{ $hostKeeper->phone }}">{{ $hostKeeper->phone }}</a>
                @endif
            </p>
        @empty
            <p>Il nostro referente ti contatterà qualche giorno prima del tuo arrivo.</p>
        @endforelse
    </div>

    <div class="section">
        <div class="section-title">La casa</div>
        <p><strong>{{ config('apartment.name') }}</strong><br>{{ $address }}</p>
        <p>
            <a href="{{ $mapLinks['google'] }}">Apri con Google Maps</a><br>
            <a href="{{ $mapLinks['apple'] }}">Apri con Apple Mappe</a><br>
            <a href="{{ $mapLinks['openstreetmap'] }}">Apri con OpenStreetMap</a>
        </p>
    </div>

    <div class="footer">
        Per qualsiasi necessità puoi rispondere a questa email.<br>
        A presto a La Caracola!
    </div>

@endsection
