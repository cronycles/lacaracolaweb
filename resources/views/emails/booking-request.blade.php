@extends('emails.layout')

@section('title', 'Nuova richiesta disponibilità')

@section('content')

    <h1>🐚 Nuova richiesta disponibilità — La Caracola</h1>

    <div class="section">
        <div class="section-title">Soggiorno richiesto</div>
        <table>
            <tr>
                <th>Check-in</th>
                <td>{{ \Carbon\Carbon::parse($requestData['checkin'])->translatedFormat('d F Y') }}</td>
            </tr>
            <tr>
                <th>Check-out</th>
                <td>{{ \Carbon\Carbon::parse($requestData['checkout'])->translatedFormat('d F Y') }}</td>
            </tr>
            <tr>
                <th>Notti</th>
                <td>{{ \Carbon\Carbon::parse($requestData['checkin'])->diffInDays(\Carbon\Carbon::parse($requestData['checkout'])) }}</td>
            </tr>
            <tr>
                <th>Adulti</th>
                <td>{{ $requestData['adults'] }}</td>
            </tr>
            @if (!empty($requestData['children']))
            <tr>
                <th>Bambini</th>
                <td>{{ $requestData['children'] }}</td>
            </tr>
            @endif
            @if (!empty($requestData['babies']))
            <tr>
                <th>Neonati (0-2 anni)</th>
                <td>{{ $requestData['babies'] }}</td>
            </tr>
            @endif
            @if (!empty($requestData['pets']))
            <tr>
                <th>Animali domestici</th>
                <td>{{ $requestData['pets'] }}</td>
            </tr>
            @endif
            <tr>
                <th>Parcheggio privato</th>
                <td>{{ !empty($requestData['parking_requested']) ? 'Richiesto' : 'No' }}</td>
            </tr>
            @if ($bookingRequest && $bookingRequest->estimated_parking_amount !== null)
            <tr>
                <th>Totale parcheggio</th>
                <td>€ {{ number_format((float) $bookingRequest->estimated_parking_amount, 2, ',', '.') }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">Ospite</div>
        <table>
            <tr>
                <th>Nome</th>
                <td>{{ $requestData['first_name'] }}</td>
            </tr>
            <tr>
                <th>Cognome</th>
                <td>{{ $requestData['last_name'] }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td><a href="mailto:{{ $requestData['email'] }}">{{ $requestData['email'] }}</a></td>
            </tr>
            @if (!empty($requestData['phone']))
            <tr>
                <th>Telefono</th>
                <td>{{ $requestData['phone'] }}</td>
            </tr>
            @endif
            <tr>
                <th>Newsletter</th>
                <td>{{ !empty($requestData['newsletter']) ? 'Sì' : 'No' }}</td>
            </tr>
        </table>
    </div>

    @if (!empty($requestData['message']))
    <div class="section">
        <div class="section-title">Messaggio</div>
        <div class="message-box">{{ $requestData['message'] }}</div>
    </div>
    @endif

    @if ($bookingRequest)
    <div class="callout">
        ✅ L'ospite ha accettato le <a href="{{ route_locale('rules') }}"><strong>Regole della Casa</strong></a> e il <a href="{{ route_locale('terms') }}"><strong>Contratto di Locazione ad Uso Turistico – La Caracola</strong></a>
        il {{ $bookingRequest->terms_accepted_at->translatedFormat('d F Y \a\l\l\e H:i') }}
        (IP: {{ $bookingRequest->ip_address ?? 'n/d' }}).
    </div>
    @endif

    <div class="footer">
        Richiesta inviata dal sito <a href="https://lacaracolaandora.com">lacaracolaandora.com</a>
        il {{ now()->translatedFormat('d F Y \a\l\l\e H:i') }}.
        <br>Rispondi direttamente a questa email per contattare l'ospite.
    </div>

@endsection

