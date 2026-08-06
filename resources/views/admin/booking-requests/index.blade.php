@extends('layouts.admin')

@section('title', 'Richieste di disponibilità')

@section('content')
    <div class="page-header-row" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h1 style="font-size:1.1rem;font-weight:700">Richieste di disponibilità</h1>
    </div>

    <div class="a-card">
        @if ($requests->isEmpty())
            <p style="color:#6b7f89;font-size:.875rem">Nessuna richiesta in attesa.</p>
        @else
            <div class="a-table-wrap">
            <table class="a-table">
                <thead>
                    <tr>
                        <th>Richiedente</th>
                        <th>Contatti</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Ospiti</th>
                        <th>Prezzo</th>
                        <th>Messaggio</th>
                        <th>Ospite associato</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $request)
                        @php $match = $matches[$request->id]; @endphp
                        <tr>
                            <td style="font-weight:600">{{ $request->full_name }}</td>
                            <td style="font-size:.85rem">
                                {{ $request->email }}
                                @if($request->phone)<br>{{ $request->phone }}@endif
                            </td>
                            <td>{{ $request->checkin->format('d/m/Y') }}</td>
                            <td>{{ $request->checkout->format('d/m/Y') }}</td>
                            <td>
                                {{ $request->adults }} adulti
                                @if($request->children > 0), {{ $request->children }} bambini @endif
                            </td>
                            <td style="font-weight:600;white-space:nowrap">
                                @if($request->estimated_total_amount !== null)
                                    € {{ number_format((float) $request->estimated_total_amount, 2, ',', '.') }}
                                @else
                                    <span style="color:#6b7f89;font-weight:400">n/d</span>
                                @endif
                            </td>
                            <td style="max-width:220px;font-size:.85rem;color:#6b7f89">
                                {{ \Illuminate\Support\Str::limit($request->message, 80) ?: '—' }}
                            </td>
                            <td>
                                @if($match)
                                    <span class="badge badge--paid" title="Verrà usato questo profilo esistente">
                                        {{ $match->full_name }} (esistente)
                                    </span>
                                @else
                                    <span class="badge badge--unpaid" title="Nessun profilo esistente corrisponde">
                                        nuovo profilo
                                    </span>
                                @endif
                            </td>
                            <td style="white-space:nowrap">
                                <form method="POST" action="{{ route('admin.booking-requests.confirm', $request) }}" style="display:inline">
                                    @csrf
                                    <button type="submit" class="btn btn--primary btn--sm">✓ Accetta</button>
                                </form>
                                <form method="POST" action="{{ route('admin.booking-requests.decline', $request) }}" style="display:inline"
                                      onsubmit="return confirm('Rifiutare questa richiesta? Verrà inviata una email all\'ospite e non verrà creata alcuna prenotazione.')">
                                    @csrf
                                    <button type="submit" class="btn btn--outline btn--sm">✕ Rifiuta</button>
                                </form>
                                <form method="POST" action="{{ route('admin.booking-requests.destroy', $request) }}" style="display:inline"
                                      onsubmit="return confirm('Eliminare definitivamente questa richiesta? Non verrà inviata alcuna email e l\'azione non è reversibile.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn--outline btn--sm">🗑 Elimina</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
@endsection
