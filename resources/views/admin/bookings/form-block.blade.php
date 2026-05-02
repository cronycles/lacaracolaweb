@extends('layouts.admin')

@section('title', 'Modifica blocco')

@section('content')
    <div style="max-width:680px">
        <div style="display:flex;gap:.75rem;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap">
            <a href="{{ route('admin.bookings.index') }}" class="btn btn--outline btn--sm">← Torna alle prenotazioni</a>
        </div>

        <div class="a-card">
            <div class="a-card__title">Modifica blocco</div>

            <form method="POST" action="{{ route('admin.bookings.update-block', $block) }}">
                @csrf
                @method('PUT')

                {{-- Reason / Type --}}
                <div class="form-group">
                    <label class="form-label" for="reason">Tipo</label>
                    <select id="reason" name="reason" class="form-select" required>
                        <option value="owner" @selected(old('reason', $block->reason) === 'owner')>Uso proprietario</option>
                        <option value="maintenance" @selected(old('reason', $block->reason) === 'maintenance')>Manutenzione</option>
                    </select>
                    @error('reason') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                {{-- Dates --}}
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="start_date">Data inizio</label>
                        <input type="date" id="start_date" name="start_date" class="form-input"
                               value="{{ old('start_date', $block->start_date->format('Y-m-d')) }}" required>
                        @error('start_date') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="end_date">Data fine</label>
                        <input type="date" id="end_date" name="end_date" class="form-input"
                               value="{{ old('end_date', $block->end_date->format('Y-m-d')) }}" required>
                        @error('end_date') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Notes --}}
                <div class="form-group">
                    <label class="form-label" for="notes">Note</label>
                    <textarea id="notes" name="notes" class="form-textarea" rows="3"
                              maxlength="1000">{{ old('notes', $block->notes) }}</textarea>
                    @error('notes') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div style="display:flex;gap:.75rem;margin-top:.5rem">
                    <button type="submit" class="btn btn--primary">
                        Salva modifiche
                    </button>
                    <a href="{{ route('admin.bookings.index') }}" class="btn btn--outline">Annulla</a>
                </div>
            </form>
        </div>
    </div>
@endsection
