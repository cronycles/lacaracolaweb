@extends('layouts.admin')

@section('title', 'Log Email Automatico')

@section('content')
<div class="a-card">
    <div class="a-card__title">Log parsing email automatico</div>
    <p style="font-size:.85rem;color:#6b7f89;margin-bottom:1rem">
        Ogni ora il sistema legge la casella IMAP e prova a creare prenotazioni dalle email non lette.
        Qui trovi l'esito di ogni email processata.
    </p>

    @php
        $statusLabels = [
            'success'   => ['label' => 'Creata',     'color' => '#dcfce7', 'text' => '#166534'],
            'error'     => ['label' => 'Errore',     'color' => '#fee2e2', 'text' => '#991b1b'],
            'skipped'   => ['label' => 'Saltata',    'color' => '#fef9c3', 'text' => '#854d0e'],
            'duplicate' => ['label' => 'Duplicata',  'color' => '#e0e7ff', 'text' => '#3730a3'],
        ];
    @endphp

    @if ($logs->isEmpty())
        <p style="color:#6b7f89;font-size:.875rem;padding:1rem 0">
            Nessuna email processata ancora. Assicurati che il cron di Laravel sia attivo e le variabili <code>IMAP_*</code> siano configurate nel <code>.env</code>.
        </p>
    @else
        <table class="a-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Mittente</th>
                    <th>Oggetto</th>
                    <th>Stato</th>
                    <th>Prenotazione</th>
                    <th>Note errore</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($logs as $log)
                    @php $s = $statusLabels[$log->status] ?? ['label' => $log->status, 'color' => '#f3f4f6', 'text' => '#374151']; @endphp
                    <tr>
                        <td style="white-space:nowrap;font-size:.8rem;color:#6b7f89">
                            {{ $log->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td style="font-size:.8rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $log->from_address }}">
                            {{ $log->from_address }}
                        </td>
                        <td style="font-size:.8rem;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $log->subject }}">
                            {{ $log->subject ?? '—' }}
                        </td>
                        <td>
                            <span class="badge" style="background:{{ $s['color'] }};color:{{ $s['text'] }}">
                                {{ $s['label'] }}
                            </span>
                        </td>
                        <td>
                            @if ($log->booking)
                                <a href="{{ route('admin.bookings.show', $log->booking) }}" class="btn btn--sm btn--outline">
                                    #{{ $log->booking_id }}
                                    {{ $log->booking->person?->first_name }} {{ $log->booking->person?->last_name }}
                                </a>
                            @else
                                —
                            @endif
                        </td>
                        <td style="font-size:.75rem;color:#9ca3af;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $log->error_message }}">
                            {{ $log->error_message ?? '' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pagination-wrap">
            {{ $logs->links() }}
        </div>
    @endif
</div>

<div class="a-card" style="margin-top:1.25rem">
    <div class="a-card__title">Configurazione richiesta</div>
    <p style="font-size:.85rem;margin-bottom:.75rem">Aggiungi queste variabili nel file <code>.env</code> di produzione:</p>
    <pre style="background:#1e3d4a;color:#c8d8e0;padding:1rem;border-radius:.375rem;font-size:.8rem;overflow-x:auto;line-height:1.6">IMAP_HOST=mail.lacaracolaandora.com
IMAP_PORT=993
IMAP_ENCRYPTION=ssl
IMAP_VALIDATE_CERT=true
IMAP_USERNAME=booking@lacaracolaandora.com
IMAP_PASSWORD=your_password</pre>
    <p style="font-size:.85rem;margin-top:.75rem">
        Poi attiva il cron di Laravel in cPanel → <strong>Cron Jobs</strong>:<br>
        <code style="background:#f3f4f6;padding:.2rem .4rem;border-radius:.25rem">* * * * * /opt/cpanel/ea-php84/root/usr/bin/php /home/lacaraco/lacaracola-app/artisan schedule:run >> /dev/null 2>&1</code>
    </p>
    <p style="font-size:.8rem;color:#6b7f89;margin-top:.5rem">
        Il comando <code>emails:parse-bookings</code> viene eseguito ogni ora in automatico. Puoi anche lanciarlo manualmente dal terminale cPanel.
    </p>
</div>
@endsection
