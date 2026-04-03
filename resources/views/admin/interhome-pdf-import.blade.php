@extends('layouts.admin')

@section('title', 'Import PDF Interhome')

@section('content')
    <div style="max-width:1000px">
        <div class="a-card">
            <div class="a-card__title">Import prenotazioni da PDF Interhome</div>
            <p style="font-size:.875rem;color:#6b7f89;margin-bottom:1rem">
                Carica il PDF esportato da Interhome, verifica l'anteprima e importa solo le prenotazioni nuove.
            </p>

            <form method="POST" action="{{ route('admin.bookings.import-pdf.preview') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group" style="max-width:560px">
                    <label for="pdf_file" class="form-label">File PDF *</label>
                    <input type="file" id="pdf_file" name="pdf_file" class="form-input" accept="application/pdf" required>
                    @error('pdf_file')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn--primary">Analizza PDF (dry-run)</button>
            </form>
        </div>

        @if (!empty($preview))
            <div class="a-card" style="border-top:3px solid #30596C">
                <div class="a-card__title">Anteprima import: {{ $preview['filename'] }}</div>

                <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1rem">
                    <span class="badge" style="background:#e0f2fe;color:#075985">Totali: {{ $preview['counts']['total'] }}</span>
                    <span class="badge" style="background:#dcfce7;color:#166534">Nuove: {{ $preview['counts']['new'] }}</span>
                    <span class="badge" style="background:#fef3c7;color:#92400e">Duplicate: {{ $preview['counts']['duplicate'] }}</span>
                    <span class="badge" style="background:#fee2e2;color:#991b1b">Saltate: {{ $preview['counts']['skipped'] }}</span>
                </div>

                @if (!empty($preview['warnings']))
                    <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:6px;padding:.75rem 1rem;margin-bottom:1rem">
                        <p style="font-size:.8rem;font-weight:600;color:#9a3412;margin-bottom:.35rem">Avvisi parser</p>
                        <ul style="margin:0;padding-left:1rem;color:#9a3412;font-size:.8rem;line-height:1.4">
                            @foreach ($preview['warnings'] as $warning)
                                <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (empty($preview['rows']))
                    <p style="color:#6b7f89;font-size:.875rem">Nessuna prenotazione valida trovata nel PDF.</p>
                @else
                    <div style="overflow:auto">
                        <table class="a-table">
                            <thead>
                                <tr>
                                    <th>Riferimento</th>
                                    <th>Ospite</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Ospiti</th>
                                    <th>Stato</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($preview['rows'] as $row)
                                    <tr>
                                        <td>{{ $row['external_ref'] ?: '—' }}</td>
                                        <td>{{ $row['first_name'] }} {{ $row['last_name'] }}</td>
                                        <td>{{ \Carbon\Carbon::parse($row['checkin'])->format('d/m/Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($row['checkout'])->format('d/m/Y') }}</td>
                                        <td>{{ $row['adults'] }}A / {{ $row['children'] }}B / {{ $row['babies'] }}N / {{ $row['pets'] ?? 0 }}P</td>
                                        <td>
                                            @if ($row['status'] === 'new')
                                                <span class="badge" style="background:#dcfce7;color:#166534">Nuova</span>
                                            @elseif ($row['status'] === 'skipped')
                                                <span class="badge" style="background:#fee2e2;color:#991b1b">Saltata</span>
                                            @else
                                                <span class="badge" style="background:#fef3c7;color:#92400e">Duplicata</span>
                                            @endif
                                            <div style="font-size:.72rem;color:#6b7f89;margin-top:.2rem">{{ $row['status_reason'] }}</div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <form method="POST" action="{{ route('admin.bookings.import-pdf.confirm') }}" style="margin-top:1rem;display:flex;gap:.75rem">
                        @csrf
                        <button type="submit" class="btn btn--accent" @disabled($preview['counts']['new'] === 0)>
                            Conferma import ({{ $preview['counts']['new'] }} nuove)
                        </button>
                        <a href="{{ route('admin.bookings.import-pdf') }}" class="btn btn--outline">Annulla anteprima</a>
                    </form>
                @endif
            </div>
        @endif

        <div class="a-card">
            <div class="a-card__title">Storico import PDF (audit)</div>

            @if (empty($recentLogs) || $recentLogs->isEmpty())
                <p style="color:#6b7f89;font-size:.875rem">Nessun import registrato finora.</p>
            @else
                <div style="overflow:auto">
                    <table class="a-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>File</th>
                                <th>Nuove</th>
                                <th>Create</th>
                                <th>Duplicate</th>
                                <th>Saltate</th>
                                <th>Errori</th>
                                <th>Utente</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentLogs as $log)
                                <tr>
                                    <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                    <td>{{ $log->file_name }}</td>
                                    <td>{{ $log->new_rows }}</td>
                                    <td>{{ $log->created_rows }}</td>
                                    <td>{{ $log->duplicate_rows }}</td>
                                    <td>{{ $log->skipped_rows }}</td>
                                    <td>{{ $log->error_rows }}</td>
                                    <td>{{ $log->importedByUser?->email ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
