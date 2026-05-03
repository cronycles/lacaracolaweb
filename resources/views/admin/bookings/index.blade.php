@extends('layouts.admin')

@section('title', 'Prenotazioni')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h1 style="font-size:1.1rem;font-weight:700">Prenotazioni</h1>
        <a href="{{ route('admin.bookings.create') }}" class="btn btn--primary">+ Nuova prenotazione</a>
    </div>

    <div class="a-card">
        @if ($items->isEmpty())
            <p style="color:#6b7f89;font-size:.875rem">Nessuna prenotazione registrata.</p>
        @else
            <table class="a-table">
                <thead>
                    <tr>
                        <th>Ospite / Tipo</th>
                        <th>Stato</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Notti</th>
                        <th>Ospiti</th>
                        <th>Origine</th>
                        <th>Pulizie</th>
                        <th>Biancheria</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        @if ($item->_type === 'booking')
                            <tr @if($item->isCanceled()) style="opacity:.72" @endif>
                                <td>
                                    <a href="{{ route('admin.bookings.show', $item) }}"
                                       style="color:#30596C;font-weight:600;text-decoration:none">
                                        {{ $item->person->full_name }}
                                    </a>
                                </td>
                                <td>
                                    @if($item->isCanceled())
                                        <span class="badge badge--canceled">cancellata</span>
                                    @else
                                        <span class="badge badge--booked">attiva</span>
                                    @endif
                                </td>
                                <td>{{ $item->checkin->format('d/m/Y') }}</td>
                                <td>{{ $item->checkout->format('d/m/Y') }}</td>
                                <td>{{ $item->nights }}</td>
                                <td>
                                    {{ $item->total_guests }}
                                    @if (($item->babies ?? 0) > 0)
                                        <span title="{{ $item->babies }} neonato/i" aria-label="{{ $item->babies }} neonato/i" style="font-size:.85em">👶</span>
                                    @endif
                                    @if ($item->pets > 0)
                                        <span title="{{ $item->pets }} animale/i" aria-label="{{ $item->pets }} animale/i" style="font-size:.85em">🐾</span>
                                    @endif
                                </td>
                                <td><span class="badge badge--{{ $item->source }}">{{ $item->source }}</span></td>
                                <td>{{ $item->cleaning_amount ? '€ ' . number_format($item->cleaning_amount, 2, ',', '.') : '—' }}</td>
                                <td>{{ $item->linen_amount ? '€ ' . number_format($item->linen_amount, 2, ',', '.') : '—' }}</td>
                                <td style="white-space:nowrap">
                                    <a href="{{ route('admin.bookings.show', $item) }}" class="btn btn--outline btn--sm">Vedi</a>
                                </td>
                            </tr>
                        @else
                            {{-- Personal block (owner/maintenance) --}}
                            <tr>
                                <td style="color:#6b7f89">
                                    <strong>{{ ucfirst($item->reason) }}</strong>
                                    @if ($item->notes)
                                        <div style="font-size:.85rem;color:#999">{{ $item->notes }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge--{{ $item->reason === 'owner' ? 'owner' : 'maintenance' }}">
                                        {{ $item->reason === 'owner' ? 'Proprietario' : 'Manutenzione' }}
                                    </span>
                                </td>
                                <td>{{ $item->start_date->format('d/m/Y') }}</td>
                                <td>{{ $item->end_date->format('d/m/Y') }}</td>
                                <td>{{ $item->start_date->diffInDays($item->end_date) }}</td>
                                <td>—</td>
                                <td>—</td>
                                <td>—</td>
                                <td>—</td>
                                <td style="white-space:nowrap">
                                    <a href="{{ route('admin.bookings.show-block', $item) }}" class="btn btn--outline btn--sm">Vedi</a>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>

            <div class="pagination-wrap">
                {{ $items->links() }}
            </div>
        @endif
    </div>
@endsection
