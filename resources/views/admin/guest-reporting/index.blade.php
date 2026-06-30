@extends('layouts.admin')

@section('title', 'Segnalazione Ospiti — Storico')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h1 style="font-size:1.1rem;font-weight:700">Segnalazione Ospiti — Storico</h1>
    </div>

    <div class="a-card">
        @if ($reports->isEmpty())
            <p style="color:#6b7f89;font-size:.875rem">Nessuna schedina inviata finora.</p>
        @else
            <table class="a-table">
                <thead>
                    <tr>
                        <th>Data invio</th>
                        <th>Prenotazione</th>
                        <th>Ospite principale</th>
                        <th>Driver</th>
                        <th>Modalità</th>
                        <th>Stato</th>
                        <th>N° ospiti</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reports as $report)
                        <tr>
                            <td style="white-space:nowrap">{{ $report->submitted_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @if ($report->booking)
                                    <a href="{{ route('admin.bookings.show', $report->booking) }}"
                                       style="color:#30596C;font-weight:600;text-decoration:none">
                                        #{{ $report->booking->id }}
                                        {{ $report->booking->checkin->format('d/m/Y') }}–{{ $report->booking->checkout->format('d/m/Y') }}
                                    </a>
                                @else
                                    <span style="color:#6b7f89">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($report->booking?->person)
                                    {{ $report->booking->person->full_name }}
                                @else
                                    <span style="color:#6b7f89">—</span>
                                @endif
                            </td>
                            <td style="font-size:.8rem;color:#6b7f89">{{ $report->driver }}</td>
                            <td>
                                @if ($report->mode === 'test')
                                    <span class="badge badge--outline">Test</span>
                                @else
                                    <span class="badge badge--primary">Invio</span>
                                @endif
                            </td>
                            <td>
                                @if ($report->status === 'success')
                                    <span class="badge badge--success">Successo</span>
                                @else
                                    <span class="badge badge--error">Errore</span>
                                @endif
                            </td>
                            <td style="text-align:center">{{ $report->guests_count }}</td>
                            <td>
                                @if ($report->booking)
                                    <a href="{{ route('admin.guest-reporting.show', $report->booking) }}"
                                       class="btn btn--outline btn--sm">Apri</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top:1rem">
                {{ $reports->links() }}
            </div>
        @endif
    </div>
@endsection
