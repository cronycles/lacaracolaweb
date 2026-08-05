@extends('emails.layout')

@section('title', 'Nuovo messaggio di contatto — La Caracola')

@section('content')

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

@endsection

