@extends('layouts.admin')

@section('title', $review->exists ? 'Modifica Recensione' : 'Nuova Recensione')

@section('content')

    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.5rem">
        <a href="{{ route('admin.reviews.index') }}" style="color:#6b7f89;font-size:.875rem">← Recensioni</a>
        <h1 style="font-size:1.1rem;font-weight:700">
            {{ $review->exists ? 'Modifica recensione' : 'Nuova recensione' }}
        </h1>
    </div>

    {{-- Booking info banner --}}
    @if($booking)
        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:.5rem;padding:.75rem 1rem;margin-bottom:1.5rem;font-size:.875rem">
            <strong>{{ $booking->person?->full_name ?? '—' }}</strong>
            · checkout {{ $booking->checkout->format('d/m/Y') }}
            @if($booking->source) · {{ $booking->source }} @endif
        </div>
    @endif

    <form method="POST"
          action="{{ $review->exists
              ? route('admin.reviews.update', $review)
              : route('admin.reviews.store', $booking) }}">
        @csrf
        @if($review->exists) @method('PUT') @endif

        <div class="a-card" style="margin-bottom:1.5rem">
            <h2 style="font-size:.9rem;font-weight:700;margin-bottom:1rem;color:#374151">Dati generali</h2>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">
                <div>
                    <label class="form-label">Nome autore <span style="color:#c62828">*</span></label>
                    <input type="text" name="author_name" class="form-input @error('author_name') is-invalid @enderror"
                           value="{{ old('author_name', $review->author_name) }}" required maxlength="255">
                    @error('author_name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Fonte (es. Interhome, Booking.com…)</label>
                    <input type="text" name="source" class="form-input @error('source') is-invalid @enderror"
                           value="{{ old('source', $review->source) }}" maxlength="255">
                    @error('source') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div style="display:grid;grid-template-columns:120px 1fr;gap:1rem;align-items:start">
                <div>
                    <label class="form-label">Stelle <span style="color:#c62828">*</span></label>
                    <select name="rating" class="form-input">
                        @for($s = 10; $s >= 1; $s--)
                            <option value="{{ $s }}" {{ old('rating', $review->rating ?? 10) == $s ? 'selected' : '' }}>
                                {{ $s }}/10
                            </option>
                        @endfor
                    </select>
                </div>
                <div style="padding-top:1.75rem">
                    <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.875rem">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active', $review->is_active ?? true) ? 'checked' : '' }}
                               style="width:1rem;height:1rem">
                        Visibile sul sito
                    </label>
                </div>
            </div>
        </div>

        {{-- Translation tabs --}}
        <div class="a-card" style="margin-bottom:1.5rem">
            <h2 style="font-size:.9rem;font-weight:700;margin-bottom:.25rem;color:#374151">Testo della recensione</h2>
            <p style="font-size:.8rem;color:#6b7f89;margin-bottom:1rem">
                L'italiano è obbligatorio. Le altre lingue sono opzionali: se assenti, verrà mostrato prima l'inglese, poi l'italiano.
            </p>

            @foreach($locales as $locale)
                @php
                    $localeLabels = ['it' => '🇮🇹 Italiano', 'en' => '🇬🇧 English', 'fr' => '🇫🇷 Français', 'de' => '🇩🇪 Deutsch'];
                    $required = $locale === 'it';
                @endphp
                <div style="margin-bottom:1rem">
                    <label class="form-label">
                        {{ $localeLabels[$locale] }}
                        @if($required) <span style="color:#c62828">*</span> @else <span style="color:#9ca3af;font-size:.75rem">(opzionale)</span> @endif
                    </label>
                    <textarea name="translations[{{ $locale }}]"
                              class="form-input @error('translations.' . $locale) is-invalid @enderror"
                              rows="4"
                              {{ $required ? 'required' : '' }}
                              placeholder="{{ $required ? 'Testo obbligatorio…' : 'Lascia vuoto per usare il fallback…' }}"
                    >{{ old('translations.' . $locale, $translations[$locale] ?? '') }}</textarea>
                    @error('translations.' . $locale) <p class="form-error">{{ $message }}</p> @enderror

                    <label class="form-label" style="margin-top:.75rem">☺ Cosa è piaciuto</label>
                    <textarea name="liked[{{ $locale }}]"
                              class="form-input @error('liked.' . $locale) is-invalid @enderror"
                              rows="2" maxlength="2000"
                              placeholder="Cosa ha apprezzato l'ospite?"
                    >{{ old('liked.' . $locale, $liked[$locale] ?? '') }}</textarea>
                    @error('liked.' . $locale) <p class="form-error">{{ $message }}</p> @enderror

                    <label class="form-label" style="margin-top:.75rem">☹ Cosa non è piaciuto</label>
                    <textarea name="disliked[{{ $locale }}]"
                              class="form-input @error('disliked.' . $locale) is-invalid @enderror"
                              rows="2" maxlength="2000"
                              placeholder="Cosa non ha apprezzato l'ospite?"
                    >{{ old('disliked.' . $locale, $disliked[$locale] ?? '') }}</textarea>
                    @error('disliked.' . $locale) <p class="form-error">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>

        <div style="display:flex;gap:.75rem">
            <button type="submit" class="btn btn--primary">
                {{ $review->exists ? 'Salva modifiche' : 'Aggiungi recensione' }}
            </button>
            <a href="{{ route('admin.reviews.index') }}" class="btn btn--outline">Annulla</a>
        </div>

    </form>

@endsection
