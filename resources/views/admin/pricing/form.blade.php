@extends('layouts.admin')

@section('title', $rule->exists ? 'Modifica regola di prezzo' : 'Nuova regola di prezzo')

@section('content')
    <div style="max-width:560px">
        <div style="margin-bottom:1rem">
            <a href="{{ route('admin.pricing.index') }}" class="btn btn--outline btn--sm">← Torna ai prezzi</a>
        </div>

        <div class="a-card">
            <div class="a-card__title">{{ $rule->exists ? 'Modifica regola' : 'Nuova regola di prezzo' }}</div>

            <form method="POST"
                  action="{{ $rule->exists ? route('admin.pricing.update', $rule) : route('admin.pricing.store') }}">
                @csrf
                @if ($rule->exists)
                    @method('PUT')
                @endif

                <div class="form-group">
                    <label class="form-label" for="name">Nome regola</label>
                    <input type="text" id="name" name="name" class="form-input"
                           value="{{ old('name', $rule->name) }}" maxlength="80" required>
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="start_date">Dal</label>
                        <input type="date" id="start_date" name="start_date" class="form-input"
                               value="{{ old('start_date', $rule->start_date?->format('Y-m-d')) }}" required>
                        @error('start_date') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="end_date">Al</label>
                        <input type="date" id="end_date" name="end_date" class="form-input"
                               value="{{ old('end_date', $rule->end_date?->format('Y-m-d')) }}" required>
                        @error('end_date') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="price_per_night">Prezzo / notte (€)</label>
                        {{-- Input is in euros; controller converts to cents --}}
                        <input type="number" id="price_per_night" name="price_per_night" class="form-input"
                               value="{{ old('price_per_night', $rule->exists ? $rule->price_euros : '') }}"
                               min="1" max="99999" step="1" required>
                        @error('price_per_night') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="min_nights">Notti minime</label>
                        <input type="number" id="min_nights" name="min_nights" class="form-input"
                               value="{{ old('min_nights', $rule->min_nights ?? 3) }}"
                               min="1" max="30" required>
                        @error('min_nights') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div style="display:flex;gap:.75rem;margin-top:.5rem">
                    <button type="submit" class="btn btn--primary">
                        {{ $rule->exists ? 'Salva modifiche' : 'Crea regola' }}
                    </button>
                    <a href="{{ route('admin.pricing.index') }}" class="btn btn--outline">Annulla</a>
                </div>
            </form>
        </div>
    </div>
@endsection
