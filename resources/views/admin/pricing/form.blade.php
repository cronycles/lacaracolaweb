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

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Dal (giorno / mese)</label>
                        <div class="form-row">
                            <div class="form-group">
                                <select id="start_day" name="start_day" class="form-select" required>
                                    @foreach (range(1, 31) as $day)
                                        <option value="{{ $day }}" @selected((int) old('start_day', $rule->start_day ?? 1) === $day)>
                                            {{ sprintf('%02d', $day) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <select id="start_month" name="start_month" class="form-select" required>
                                    @foreach (range(1, 12) as $month)
                                        <option value="{{ $month }}" @selected((int) old('start_month', $rule->start_month ?? 1) === $month)>
                                            {{ sprintf('%02d', $month) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @error('start_day') <div class="form-error">{{ $message }}</div> @enderror
                        @error('start_month') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Al (giorno / mese)</label>
                        <div class="form-row">
                            <div class="form-group">
                                <select id="end_day" name="end_day" class="form-select" required>
                                    @foreach (range(1, 31) as $day)
                                        <option value="{{ $day }}" @selected((int) old('end_day', $rule->end_day ?? 1) === $day)>
                                            {{ sprintf('%02d', $day) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <select id="end_month" name="end_month" class="form-select" required>
                                    @foreach (range(1, 12) as $month)
                                        <option value="{{ $month }}" @selected((int) old('end_month', $rule->end_month ?? 1) === $month)>
                                            {{ sprintf('%02d', $month) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @error('end_day') <div class="form-error">{{ $message }}</div> @enderror
                        @error('end_month') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="price_per_night">Prezzo / notte (€)</label>
                    <input type="number" id="price_per_night" name="price_per_night" class="form-input"
                           value="{{ old('price_per_night', $rule->exists ? $rule->price_euros : '') }}"
                           min="1" max="99999" step="1" required>
                    @error('price_per_night') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="note">Nota (opzionale)</label>
                    <textarea id="note" name="note" class="form-input" rows="3"
                              maxlength="1000"
                              placeholder="Es. Alta stagione, prezzi di mercato luglio…">{{ old('note', $rule->note ?? '') }}</textarea>
                    @error('note') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="year">Anno specifico (opzionale)</label>
                    <input type="number" id="year" name="year" class="form-input"
                           value="{{ old('year', $rule->year ?? '') }}"
                           min="2020" max="2100" step="1"
                           placeholder="Lascia vuoto per regola ricorrente ogni anno">
                    <p style="font-size:.8rem;color:#6b7f89;margin-top:.25rem">
                        Se impostato, la regola vale <strong>solo</strong> per quell'anno e ha priorità sulle regole
                        ricorrenti (utile per feste mobili come Pasqua/Pentecoste, che cambiano data ogni anno).
                    </p>
                    @error('year') <div class="form-error">{{ $message }}</div> @enderror
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
