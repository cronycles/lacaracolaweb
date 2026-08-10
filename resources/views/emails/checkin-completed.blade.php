@extends('emails.layout')

@section('title', 'Check-in online completato — La Caracola')

@section('content')

    <h1>🐚 Grazie, il tuo check-in è completato!</h1>

    <p>Abbiamo ricevuto correttamente tutti i dati del check-in online. È tutto pronto per il tuo arrivo a La Caracola: non devi fare altro per il check-in online.</p>

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
        </table>
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

    <div class="section">
        <div class="section-title">Cancellazione gratuita</div>
        <p>Potrai cancellare gratuitamente la prenotazione, con rimborso completo, fino al {{ $freeCancellationDate->translatedFormat('d F Y') }}.</p>
    </div>

    @if ($booking->bookingRequest?->terms_accepted_at)
    <div class="callout">
        {!! __('app.booking_confirmed_mail_terms', [
            'date'      => $booking->bookingRequest->terms_accepted_at->translatedFormat('d F Y \\a\\l\\l\\e H:i'),
            'rules_url' => route_locale('rules'),
            'terms_url' => route_locale('terms'),
        ]) !!}
    </div>
    @endif

    <div class="footer">
        Per qualsiasi necessità puoi rispondere a questa email.<br>
        A presto a La Caracola!
    </div>

@endsection
