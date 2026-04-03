@extends('layouts.admin')

@section('title', 'Calendario disponibilità')

@section('content')
    @php
        use Carbon\Carbon;

        $months = [
            Carbon::now()->startOfMonth(),
            Carbon::now()->addMonth()->startOfMonth(),
            Carbon::now()->addMonths(2)->startOfMonth(),
        ];

        $bookedDays = [];
        foreach ($bookings as $bookingItem) {
            $cursor = Carbon::parse($bookingItem->checkin)->startOfDay();
            $checkoutDate = Carbon::parse($bookingItem->checkout)->startOfDay();

            while ($cursor->lt($checkoutDate)) {
                $bookedDays[$cursor->format('Y-m-d')] = true;
                $cursor->addDay();
            }
        }

        $ownerDays = [];
        $maintenanceDays = [];
        foreach ($blocks as $blockItem) {
            $cursor = Carbon::parse($blockItem->start_date)->startOfDay();
            $blockEnd = Carbon::parse($blockItem->end_date)->startOfDay();
            $blockType = $blockItem->type ?? $blockItem->reason;

            while ($cursor->lte($blockEnd)) {
                $dateKey = $cursor->format('Y-m-d');
                if ($blockType === 'owner') {
                    $ownerDays[$dateKey] = true;
                } else {
                    $maintenanceDays[$dateKey] = true;
                }
                $cursor->addDay();
            }
        }
    @endphp

    <div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start">

        {{-- Left: event list --}}
        <div>
            <div class="a-card">
                <div class="a-card__title">Prenotazioni attive</div>

                <div class="cal-grid-wrap">
                    @foreach ($months as $month)
                        @php
                            $monthStart = $month->copy();
                            $daysInMonth = $monthStart->daysInMonth;
                            $firstWeekDay = $monthStart->dayOfWeekIso;
                        @endphp

                        <div class="cal-month">
                            <div class="cal-month__title">{{ $monthStart->translatedFormat('F Y') }}</div>

                            <div class="cal-month__weekdays">
                                <span class="cal-month__weekday">Lun</span>
                                <span class="cal-month__weekday">Mar</span>
                                <span class="cal-month__weekday">Mer</span>
                                <span class="cal-month__weekday">Gio</span>
                                <span class="cal-month__weekday">Ven</span>
                                <span class="cal-month__weekday">Sab</span>
                                <span class="cal-month__weekday">Dom</span>
                            </div>

                            <div class="cal-month__days">
                                @for ($i = 1; $i < $firstWeekDay; $i++)
                                    <span class="cal-day cal-day--blank" aria-hidden="true"></span>
                                @endfor

                                @for ($day = 1; $day <= $daysInMonth; $day++)
                                    @php
                                        $currentDate = $monthStart->copy()->day($day);
                                        $dateKey = $currentDate->format('Y-m-d');
                                        $dayClass = '';

                                        if (isset($ownerDays[$dateKey])) {
                                            $dayClass = 'cal-day--owner';
                                        } elseif (isset($maintenanceDays[$dateKey])) {
                                            $dayClass = 'cal-day--maintenance';
                                        } elseif (isset($bookedDays[$dateKey])) {
                                            $dayClass = 'cal-day--booked';
                                        }
                                    @endphp

                                    <span class="cal-day {{ $dayClass }}">{{ $day }}</span>
                                @endfor
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Legend --}}
                <div class="cal-legend">
                    <span><span class="cal-legend__dot dot-booked"></span>Prenotazione</span>
                    <span><span class="cal-legend__dot dot-owner"></span>Uso proprietario</span>
                    <span><span class="cal-legend__dot dot-maintenance"></span>Manutenzione</span>
                </div>

                @if ($bookings->isEmpty() && $blocks->isEmpty())
                    <p style="color:#6b7f89;font-size:.875rem">Nessun evento futuro.</p>
                @else
                    <ul class="event-list">
                        @foreach ($bookings as $booking)
                            <li class="event-item">
                                <div class="event-item__bar event-item__bar--booked"></div>
                                <div style="flex:1">
                                    <div class="event-item__name">
                                        <a href="{{ route('admin.bookings.show', $booking) }}" style="color:#30596C;text-decoration:none">
                                            {{ $booking->person->full_name }}
                                        </a>
                                    </div>
                                    <div class="event-item__dates">
                                        {{ $booking->checkin->format('d/m/Y') }} → {{ $booking->checkout->format('d/m/Y') }}
                                        · {{ $booking->nights }} notti
                                        · {{ $booking->total_guests }} ospiti
                                    </div>
                                    <div style="margin-top:.25rem">
                                        <span class="badge badge--{{ $booking->source }}">{{ $booking->source }}</span>
                                        @if ($booking->external_ref)
                                            <span style="font-size:.75rem;color:#6b7f89;margin-left:.4rem">{{ $booking->external_ref }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div style="flex-shrink:0">
                                    <a href="{{ route('admin.bookings.edit', $booking) }}" class="btn btn--outline btn--sm">Modifica</a>
                                </div>
                            </li>
                        @endforeach

                        @foreach ($blocks as $block)
                            <li class="event-item">
                                <div class="event-item__bar event-item__bar--{{ $block->reason }}"></div>
                                <div style="flex:1">
                                    <div class="event-item__name">
                                        <span class="badge badge--{{ $block->reason }}">{{ $block->reason }}</span>
                                    </div>
                                    <div class="event-item__dates">
                                        {{ $block->start_date->format('d/m/Y') }} → {{ $block->end_date->format('d/m/Y') }}
                                        @if ($block->notes)
                                            · {{ $block->notes }}
                                        @endif
                                    </div>
                                </div>
                                <div style="flex-shrink:0">
                                    <form method="POST" action="{{ route('admin.calendar.block.destroy', $block) }}"
                                          onsubmit="return confirm('Eliminare questo blocco?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn--danger btn--sm">Rimuovi</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- Right: add manual block form --}}
        <div>
            <div class="a-card">
                <div class="a-card__title">Aggiungi blocco manuale</div>
                <form method="POST" action="{{ route('admin.calendar.block') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="start_date">Dal</label>
                        <input type="date" id="start_date" name="start_date" class="form-input"
                               value="{{ old('start_date') }}" required>
                        @error('start_date')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="end_date">Al</label>
                        <input type="date" id="end_date" name="end_date" class="form-input"
                               value="{{ old('end_date') }}" required>
                        @error('end_date')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="reason">Motivo</label>
                        <select id="reason" name="reason" class="form-select" required>
                            <option value="">— Seleziona —</option>
                            <option value="owner"       @selected(old('reason') === 'owner')>Uso proprietario</option>
                            <option value="maintenance" @selected(old('reason') === 'maintenance')>Manutenzione</option>
                        </select>
                        @error('reason')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="notes">Note (opzionale)</label>
                        <input type="text" id="notes" name="notes" class="form-input"
                               value="{{ old('notes') }}" maxlength="255">
                    </div>
                    <button type="submit" class="btn btn--primary" style="width:100%">Aggiungi blocco</button>
                </form>
            </div>

            <div class="a-card">
                <div class="a-card__title">Aggiungi prenotazione</div>
                <a href="{{ route('admin.bookings.create') }}" class="btn btn--outline" style="width:100%;justify-content:center">
                    + Nuova prenotazione
                </a>
            </div>
        </div>
    </div>
@endsection
