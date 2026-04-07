<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Nuova richiesta disponibilità</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; font-size: 15px; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 24px; }
        h1 { font-size: 20px; color: #1a5a5a; border-bottom: 2px solid #1a5a5a; padding-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        th { text-align: left; font-weight: bold; width: 40%; color: #555; padding: 6px 0; }
        td { padding: 6px 0; }
        .section { margin-top: 20px; }
        .section-title { font-weight: bold; color: #1a5a5a; margin-bottom: 4px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; }
        .message-box { background: #f5f5f5; border-left: 4px solid #1a5a5a; padding: 12px 16px; border-radius: 4px; margin-top: 8px; white-space: pre-wrap; }
        .footer { margin-top: 32px; font-size: 12px; color: #888; border-top: 1px solid #eee; padding-top: 16px; }
        .badge { display: inline-block; background: #1a5a5a; color: #fff; border-radius: 4px; padding: 2px 10px; font-size: 13px; }
    </style>
</head>
<body>
<div class="container">

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
        </table>
    </div>

    <div class="section">
        <div class="section-title">Ospite</div>
        <table>
            <tr>
                <th>Nome</th>
                <td>{{ $requestData['name'] }}</td>
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

    <div class="footer">
        Richiesta inviata dal sito <a href="https://lacaracolaandora.com">lacaracolaandora.com</a>
        il {{ now()->translatedFormat('d F Y \a\l\l\e H:i') }}.
        <br>Rispondi direttamente a questa email per contattare l'ospite.
    </div>

</div>
</body>
</html>
