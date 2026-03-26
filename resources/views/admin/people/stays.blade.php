@extends('layouts.admin')

@section('title', 'Soggiorni — ' . $person->full_name)

@section('content')
    <div style="max-width:760px">
        <div style="display:flex;gap:.75rem;margin-bottom:1rem;align-items:center">
            <a href="{{ route('admin.people.show', $person) }}" class="btn btn--outline btn--sm">← {{ $person->full_name }}</a>
        </div>

        <h1 style="font-size:1.1rem;font-weight:700;margin-bottom:1rem">
            Soggiorni di {{ $person->full_name }}
        </h1>

        {{-- Date range filter --}}
        <form method="GET" action="{{ route('admin.people.stays', $person) }}"
              style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;align-items:flex-end">
            <div>
                <label class="form-label" for="from">Dal</label>
                <input type="date" id="from" name="from" class="form-input" style="width:auto"
                       value="{{ request('from') }}">
            </div>
            <div>
                <label class="form-label" for="to">Al</label>
                <input type="date" id="to" name="to" class="form-input" style="width:auto"
                       value="{{ request('to') }}">
            </div>
            <button type="submit" class="btn btn--outline">Filtra</button>
            @if (request('from') || request('to'))
                <a href="{{ route('admin.people.stays', $person) }}" class="btn btn--outline">✕ Reset</a>
            @endif
        </form>

        <div class="a-card">
            @if ($bookings->isEmpty())
                <p style="color:#6b7f89;font-size:.875rem">Nessun soggiorno trovato.</p>
            @else
                <table class="a-table">
                    <thead>
                        <tr>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Notti</th>
                            <th>Adulti</th>
                            <th>Bambini</th>
                            <th>Origine</th>
                            <th>Rif.</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bookings as $booking)
                            <tr>
                                <td>{{ $booking->checkin->format('d/m/Y') }}</td>
                                <td>{{ $booking->checkout->format('d/m/Y') }}</td>
                                <td>{{ $booking->nights }}</td>
                                <td>{{ $booking->adults }}</td>
                                <td>{{ ($booking->children ?? 0) + ($booking->babies ?? 0) }}</td>
                                <td><span class="badge badge--{{ $booking->source }}">{{ $booking->source }}</span></td>
                                <td style="font-size:.8rem;color:#6b7f89">{{ $booking->external_ref ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('admin.bookings.show', $booking) }}"
                                       class="btn btn--outline btn--sm">Vedi</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="pagination-wrap">
                    {{ $bookings->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
