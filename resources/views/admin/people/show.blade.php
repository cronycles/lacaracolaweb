@extends('layouts.admin')

@section('title', $person->full_name)

@section('content')
    <div style="max-width:720px">
        <div style="display:flex;gap:.75rem;margin-bottom:1rem;align-items:center">
            <a href="{{ route('admin.people.index') }}" class="btn btn--outline btn--sm">← Ospiti</a>
            <a href="{{ route('admin.people.edit', ['ospiti' => $person, 'return_to' => route('admin.people.show', $person)]) }}" class="btn btn--primary btn--sm">Modifica</a>
            <a href="{{ route('admin.people.stays', $person) }}" class="btn btn--outline btn--sm">Soggiorni</a>
            <form method="POST" action="{{ route('admin.people.destroy', $person) }}"
                  onsubmit="return confirm('Eliminare questo ospite?')" style="margin-left:auto">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn--danger btn--sm">Elimina</button>
            </form>
        </div>

        {{-- Personal data --}}
        <div class="a-card">
            <div class="a-card__title">Dati personali</div>
            <table class="a-table">
                <tbody>
                    <tr>
                        <th style="width:180px">Nome completo</th>
                        <td>{{ $person->full_name }}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>{{ $person->email ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Telefono</th>
                        <td>{{ $person->phone ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Data di nascita</th>
                        <td>{{ $person->birth_date?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Paese</th>
                        <td>{{ $person->country_display ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Documento</th>
                        <td>
                            @if ($person->document_type || $person->document_number)
                                {{ $person->document_type }} {{ $person->document_number }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Newsletter</th>
                        <td>
                            @if ($person->newsletter_subscribed)
                                <span class="badge badge--booked">Iscritto</span>
                                @if ($person->newsletter_subscribed_at)
                                    <span style="font-size:.75rem;color:#6b7f89;margin-left:.4rem">
                                        dal {{ $person->newsletter_subscribed_at->format('d/m/Y') }}
                                    </span>
                                @endif
                            @else
                                <span style="color:#9ca3af">Non iscritto</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Bookings --}}
        <div class="a-card">
            <div class="a-card__title">Soggiorni attivi ({{ $person->bookings->whereNull('canceled_at')->count() }})</div>
            @if ($person->bookings->whereNotNull('canceled_at')->count() > 0)
                <p style="font-size:.8rem;color:#6b7f89;margin-bottom:.75rem">
                    Storico totale: {{ $person->bookings->count() }} (incluse {{ $person->bookings->whereNotNull('canceled_at')->count() }} cancellate)
                </p>
            @endif

            @if ($person->bookings->isEmpty())
                <p style="color:#6b7f89;font-size:.875rem">Nessun soggiorno registrato.</p>
            @else
                <table class="a-table">
                    <thead>
                        <tr>
                            <th>Stato</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Notti</th>
                            <th>Ospiti</th>
                            <th>Origine</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($person->bookings->sortByDesc('checkin') as $booking)
                            <tr @if($booking->isCanceled()) style="opacity:.72" @endif>
                                <td>
                                    @if($booking->isCanceled())
                                        <span class="badge badge--canceled">cancellata</span>
                                    @else
                                        <span class="badge badge--booked">attiva</span>
                                    @endif
                                </td>
                                <td>{{ $booking->checkin->format('d/m/Y') }}</td>
                                <td>{{ $booking->checkout->format('d/m/Y') }}</td>
                                <td>{{ $booking->nights }}</td>
                                <td>
                                    {{ $booking->total_guests }}
                                    @if (($booking->babies ?? 0) > 0)
                                        <span title="{{ $booking->babies }} neonato/i" aria-label="{{ $booking->babies }} neonato/i" style="font-size:.85em">👶</span>
                                    @endif
                                    @if (($booking->pets ?? 0) > 0)
                                        <span title="{{ $booking->pets }} animale/i" aria-label="{{ $booking->pets }} animale/i" style="font-size:.85em">🐾</span>
                                    @endif
                                </td>
                                <td><span class="badge badge--{{ $booking->source }}">{{ $booking->source }}</span></td>
                                <td>
                                    <a href="{{ route('admin.bookings.show', $booking) }}"
                                       class="btn btn--outline btn--sm">Dettaglio</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
