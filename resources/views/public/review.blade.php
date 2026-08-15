@extends('layouts.app')

@section('title', __('app.review_page_title') . ' — ' . config('apartment.name'))

@section('content')
<section class="section">
    <div class="container" style="max-width:720px">
        <h1 class="section-title">{{ __('app.review_title') }}</h1>

        <div class="checkin-lang-switch">
            @foreach ($locales as $locale)
                <a href="{{ request()->fullUrlWithQuery(['lang' => $locale]) }}"
                   class="{{ app()->getLocale() === $locale ? 'is-active' : '' }}">{{ strtoupper($locale) }}</a>
            @endforeach
        </div>

        <div class="checkin-callout" style="margin-bottom:var(--space-6)">
            <strong>{{ $booking->person?->full_name }}</strong>
            <span>{{ $booking->checkin->translatedFormat('d F Y') }} — {{ $booking->checkout->translatedFormat('d F Y') }}</span>
        </div>

        @php
            $editing = ! $review || session('review_editing_'.$booking->id);
            $translation = $review?->translations->firstWhere('locale', app()->getLocale())
                ?? $review?->translations->firstWhere('locale', $review->original_locale)
                ?? $review?->translations->first();
        @endphp

        @if (! $editing && $review)
            <div class="checkin-callout checkin-callout--success" role="status">
                <strong class="checkin-callout__title">{{ __('app.review_submitted_title') }}</strong>
                <span>{{ __('app.review_submitted_text') }}</span>
                <div class="checkin-summary">
                    <section class="checkin-summary__guest">
                        <h2>{{ $review->rating }} / 10</h2>
                        @if($translation?->text)<p style="white-space:pre-line">{{ $translation->text }}</p>@endif
                        @if($translation?->liked_text)<p><strong>{{ __('app.review_liked_label') }}</strong><br>{{ $translation->liked_text }}</p>@endif
                        @if($translation?->disliked_text)<p><strong>{{ __('app.review_disliked_label') }}</strong><br>{{ $translation->disliked_text }}</p>@endif
                    </section>
                </div>
                    <form method="POST" action="{{ route('review.edit', $booking->review_token) }}"
                        onsubmit="return confirm('{{ __('app.review_edit_confirm') }}')">
                    @csrf
                    <button type="submit" class="btn btn--outline">{{ __('app.review_edit_button') }}</button>
                </form>
            </div>
        @else
            <p style="color:var(--color-text-muted);margin-bottom:var(--space-6)">{{ __('app.review_intro') }}</p>

            @if ($errors->any())
                <div class="checkin-callout checkin-callout--error" role="alert">{{ __('app.review_validation_error') }}</div>
            @endif

            <form method="POST" action="{{ route('review.confirm', $booking->review_token) }}" class="booking-form">
                @csrf
                <div class="booking-form__group">
                    <label for="review_rating">{{ __('app.review_rating_label') }} *</label>
                    <select id="review_rating" name="rating" required>
                        <option value="">—</option>
                        @for($rating = 10; $rating >= 1; $rating--)
                            <option value="{{ $rating }}" @selected((int) old('rating', $review?->rating) === $rating)>{{ $rating }} / 10</option>
                        @endfor
                    </select>
                    @error('rating') <span class="booking-form__field-error">{{ $message }}</span> @enderror
                </div>
                <div class="booking-form__group">
                    <label for="review_text">{{ __('app.review_text_label') }} *</label>
                    <textarea id="review_text" name="text" rows="6" maxlength="10000" required>{{ old('text', $translation?->text) }}</textarea>
                    @error('text') <span class="booking-form__field-error">{{ $message }}</span> @enderror
                </div>
                <div class="booking-form__group">
                    <label for="review_liked_text">{{ __('app.review_liked_label') }}</label>
                    <textarea id="review_liked_text" name="liked_text" rows="3" maxlength="2000">{{ old('liked_text', $translation?->liked_text) }}</textarea>
                    @error('liked_text') <span class="booking-form__field-error">{{ $message }}</span> @enderror
                </div>
                <div class="booking-form__group">
                    <label for="review_disliked_text">{{ __('app.review_disliked_label') }}</label>
                    <textarea id="review_disliked_text" name="disliked_text" rows="3" maxlength="2000">{{ old('disliked_text', $translation?->disliked_text) }}</textarea>
                    @error('disliked_text') <span class="booking-form__field-error">{{ $message }}</span> @enderror
                </div>
                <div class="booking-form__group">
                    <label for="review_private_comment">{{ __('app.review_private_comment_label') }}</label>
                    <p style="color:var(--color-text-muted);font-size:.9rem;margin-top:0">{{ __('app.review_private_comment_help') }}</p>
                    <textarea id="review_private_comment" name="private_comment" rows="4" maxlength="2000">{{ old('private_comment', $review?->private_comment) }}</textarea>
                    @error('private_comment') <span class="booking-form__field-error">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="btn btn--primary booking-form__submit">{{ __('app.review_confirm_button') }}</button>
            </form>
        @endif
    </div>
</section>
@endsection
