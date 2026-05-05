@extends('layouts.admin')

@section('title', 'Calendario disponibilità')

@section('content')
    <div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start">

        {{-- Left: event list --}}
        <div>
            <div class="a-card">
                <div class="a-card__title">Eventi nel periodo visualizzato</div>

                <div class="cal-window-nav">
                    <div class="cal-window-nav__pager">
                        <a href="{{ route('admin.calendar', ['month' => $previousWindowMonth]) }}" class="btn btn--outline btn--sm cal-window-nav__btn" aria-label="Vai al mese precedente">←</a>
                        <span class="cal-window-nav__label">{{ ucfirst($windowLabel) }}</span>
                        <a href="{{ route('admin.calendar', ['month' => $nextWindowMonth]) }}" class="btn btn--outline btn--sm cal-window-nav__btn" aria-label="Vai al mese successivo">→</a>
                    </div>
                    <div class="cal-window-nav__controls">
                        <form method="GET" action="{{ route('admin.calendar') }}" class="cal-window-nav__picker">
                            <label for="month" class="cal-window-nav__picker-label">Vai a:</label>
                            <select id="month" name="month" class="form-select form-select--sm">
                                @foreach ($selectorMonths as $option)
                                    <option value="{{ $option['value'] }}" @selected($windowCenterMonth === $option['value'])>
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn--outline btn--sm">Vai</button>
                        </form>
                        <a href="{{ route('admin.calendar') }}" class="btn btn--outline btn--sm cal-window-nav__today">Oggi</a>
                    </div>
                </div>

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
                                        $dayClasses = ['cal-day'];

                                        if (isset($ownerDays[$dateKey]) || isset($ownerArrivalDays[$dateKey]) || isset($ownerDepartureDays[$dateKey])) {
                                            if (isset($ownerDays[$dateKey])) {
                                                $dayClasses[] = 'cal-day--owner';
                                            }
                                            if (isset($ownerArrivalDays[$dateKey]) && isset($ownerDepartureDays[$dateKey])) {
                                                $dayClasses[] = 'cal-day--owner-arrival-departure';
                                            } elseif (isset($ownerArrivalDays[$dateKey])) {
                                                $dayClasses[] = 'cal-day--owner-arrival';
                                            } elseif (isset($ownerDepartureDays[$dateKey])) {
                                                $dayClasses[] = 'cal-day--owner-departure';
                                            }
                                        } elseif (isset($maintenanceDays[$dateKey]) || isset($maintenanceArrivalDays[$dateKey]) || isset($maintenanceDepartureDays[$dateKey])) {
                                            if (isset($maintenanceDays[$dateKey])) {
                                                $dayClasses[] = 'cal-day--maintenance';
                                            }
                                            if (isset($maintenanceArrivalDays[$dateKey]) && isset($maintenanceDepartureDays[$dateKey])) {
                                                $dayClasses[] = 'cal-day--maintenance-arrival-departure';
                                            } elseif (isset($maintenanceArrivalDays[$dateKey])) {
                                                $dayClasses[] = 'cal-day--maintenance-arrival';
                                            } elseif (isset($maintenanceDepartureDays[$dateKey])) {
                                                $dayClasses[] = 'cal-day--maintenance-departure';
                                            }
                                        } else {
                                            if (isset($bookedDays[$dateKey])) {
                                                $dayClasses[] = 'cal-day--booked';
                                            }
                                            if (isset($arrivalDays[$dateKey]) && isset($departureDays[$dateKey])) {
                                                $dayClasses[] = 'cal-day--arrival-departure';
                                            } elseif (isset($arrivalDays[$dateKey])) {
                                                $dayClasses[] = 'cal-day--arrival';
                                            } elseif (isset($departureDays[$dateKey])) {
                                                $dayClasses[] = 'cal-day--departure';
                                            }
                                        }
                                        if ($dateKey === $today) {
                                            $dayClasses[] = 'cal-day--today';
                                        }
                                    @endphp

                                    <span @class($dayClasses)>{{ $day }}</span>
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
                    <p style="color:#6b7f89;font-size:.875rem">Nessun evento nel periodo selezionato.</p>
                @else
                    <ul class="event-list">
                        @foreach ($bookings as $booking)
                            <li class="event-item">
                                <div class="event-item__bar event-item__bar--{{ $booking->isCanceled() ? 'canceled' : 'booked' }}"></div>
                                <div style="flex:1">
                                    <div class="event-item__name" @if($booking->isCanceled()) style="color:#9ca3af" @endif>
                                        <a href="{{ route('admin.bookings.show', $booking) }}" @if($booking->isCanceled()) style="color:#9ca3af;text-decoration:line-through" @else style="color:#30596C;text-decoration:none" @endif>
                                            {{ $booking->person->full_name }}
                                        </a>
                                    </div>
                                    <div class="event-item__dates" @if($booking->isCanceled()) style="color:#9ca3af" @endif>
                                        {{ $booking->checkin->format('d/m/Y') }} → {{ $booking->checkout->format('d/m/Y') }}
                                        · {{ $booking->nights }} notti
                                        · {{ $booking->total_guests }} posti letto
                                        @if (($booking->babies ?? 0) > 0)
                                            · 👶 {{ $booking->babies }}
                                        @endif
                                        @if (($booking->pets ?? 0) > 0)
                                            · 🐾 {{ $booking->pets }}
                                        @endif
                                    </div>
                                    <div style="margin-top:.25rem">
                                        <span class="badge badge--{{ $booking->source }}">{{ $booking->source }}</span>
                                        @if($booking->isCanceled())
                                            <span class="badge badge--canceled">cancellata</span>
                                        @endif
                                        @if ($booking->external_ref)
                                            <span style="font-size:.75rem;color:#6b7f89;margin-left:.4rem">{{ $booking->external_ref }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div style="flex-shrink:0">
                                    <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn--outline btn--sm">Dettaglio</a>
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
                                    <a href="{{ route('admin.bookings.show-block', $block) }}" class="btn btn--outline btn--sm">Dettaglio</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- Right: add manual block form (manage_calendar only) --}}
        <div>
            @if(auth()->user()->hasPermission('manage_calendar'))
            <div class="a-card">
                <div class="a-card__title">Aggiungi blocco manuale</div>
                <form method="POST" action="{{ route('admin.calendar.block', ['month' => $windowCenterMonth]) }}">
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
            @endif
        </div>
    </div>
@endsection
