@extends('layouts.admin')

@section('title', $rule->exists ? 'Modifica sconto soggiorno' : 'Nuovo sconto soggiorno')

@section('content')
    <div style="max-width:560px">
        <div style="margin-bottom:1rem">
            <a href="{{ route('admin.stay-discounts.index') }}" class="btn btn--outline btn--sm">← Torna agli sconti</a>
        </div>

        <div class="a-card">
            <div class="a-card__title">{{ $rule->exists ? 'Modifica regola sconto' : 'Nuova regola sconto soggiorno' }}</div>

            <form method="POST"
                  action="{{ $rule->exists ? route('admin.stay-discounts.update', $rule) : route('admin.stay-discounts.store') }}">
                @csrf
                @if ($rule->exists)
                    @method('PUT')
                @endif

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="min_nights">Notti minime</label>
                        <input type="number" id="min_nights" name="min_nights" class="form-input"
                               value="{{ old('min_nights', $rule->min_nights ?? 4) }}" min="1" max="365" required>
                        @error('min_nights') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="max_nights">Notti massime (opzionale)</label>
                        <input type="number" id="max_nights" name="max_nights" class="form-input"
                               value="{{ old('max_nights', $rule->max_nights) }}" min="1" max="365">
                        @error('max_nights') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="discount_percent">Sconto %</label>
                        <input type="number" id="discount_percent" name="discount_percent" class="form-input"
                               value="{{ old('discount_percent', $rule->discount_percent ?? 5) }}" min="1" max="90" required>
                        @error('discount_percent') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="priority">Priorità (più basso = prima)</label>
                        <input type="number" id="priority" name="priority" class="form-input"
                               value="{{ old('priority', $rule->priority ?? 100) }}" min="1" max="9999" required>
                        @error('priority') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-group" style="margin-top:.25rem">
                    <label style="display:inline-flex;align-items:center;gap:.5rem;font-size:.9rem;color:#374151;cursor:pointer">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $rule->exists ? $rule->is_active : true))>
                        Regola attiva
                    </label>
                    @error('is_active') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <p style="font-size:.8rem;color:#6b7f89;margin-bottom:.9rem">
                    Se lasci "notti massime" vuoto, la regola vale da "notti minime" in su (es. 7+).
                </p>

                <div style="display:flex;gap:.75rem;margin-top:.5rem">
                    <button type="submit" class="btn btn--primary">
                        {{ $rule->exists ? 'Salva modifiche' : 'Crea regola' }}
                    </button>
                    <a href="{{ route('admin.stay-discounts.index') }}" class="btn btn--outline">Annulla</a>
                </div>
            </form>
        </div>
    </div>
@endsection
