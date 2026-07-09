<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Nuovo messaggio di contatto — La Caracola</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; font-size: 15px; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 24px; }
        h1 { font-size: 20px; color: #1a5a5a; border-bottom: 2px solid #1a5a5a; padding-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        th { text-align: left; font-weight: bold; width: 35%; color: #555; padding: 6px 0; vertical-align: top; }
        td { padding: 6px 0; }
        .section { margin-top: 20px; }
        .section-title { font-weight: bold; color: #1a5a5a; margin-bottom: 4px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; }
        .message-box { background: #f5f5f5; border-left: 4px solid #1a5a5a; padding: 12px 16px; border-radius: 4px; margin-top: 8px; white-space: pre-wrap; }
        .footer { margin-top: 32px; font-size: 12px; color: #888; border-top: 1px solid #eee; padding-top: 16px; }
    </style>
</head>
<body>
<div class="container">

    <h1>🐚 Nuovo messaggio di contatto — La Caracola</h1>

    <div class="section">
        <div class="section-title">Mittente</div>
        <table>
            <tr>
                <th>Nome</th>
                <td>{{ $data['name'] }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td><a href="mailto:{{ $data['email'] }}">{{ $data['email'] }}</a></td>
            </tr>
            @if (!empty($data['subject']))
            <tr>
                <th>Oggetto</th>
                <td>{{ $data['subject'] }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">Messaggio</div>
        <div class="message-box">{{ $data['message'] }}</div>
    </div>

    <div class="footer">
        Messaggio inviato tramite il modulo di contatto di <strong>lacaracolaandora.com</strong>.<br>
        Per rispondere, usa direttamente la Reply-To di questa email.
    </div>

</div>
</body>
</html>
