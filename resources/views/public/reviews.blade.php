@extends('layouts.app')

@section('title', __('app.reviews_title') . ' — ' . config('apartment.name'))

@section('content')

<section class="section">
    <div class="container">
        <h1 class="section-title" style="text-align:center">{{ __('app.reviews_title') }}</h1>
        <p class="section-subtitle" style="text-align:center;margin-inline:auto">{{ __('app.reviews_subtitle') }}</p>

        @if($reviews->isEmpty())
            <p style="text-align:center;color:var(--color-text-muted);margin-top:var(--space-8)">
                {{ __('app.reviews_empty') }}
            </p>
        @else
            <div class="reviews-grid">
                @foreach($reviews as $review)
                    <div class="review-card">
                        <div class="review-card__stars">
                            @for($i = 1; $i <= 10; $i++)
                                {{ $i <= $review->rating ? '★' : '☆' }}
                            @endfor
                        </div>
                        <p class="review-card__text">"{{ $review->textForLocale(app()->getLocale()) }}"</p>
                        @if($review->likedTextForLocale(app()->getLocale()))
                            <div class="review-card__feedback review-card__feedback--liked">
                                <span class="review-card__feedback-icon" aria-hidden="true">☺</span>
                                <div>
                                    <strong>{{ __('app.reviews_liked') }}</strong>
                                    <p>{{ $review->likedTextForLocale(app()->getLocale()) }}</p>
                                </div>
                            </div>
                        @endif
                        @if($review->dislikedTextForLocale(app()->getLocale()))
                            <div class="review-card__feedback review-card__feedback--disliked">
                                <span class="review-card__feedback-icon" aria-hidden="true">☹</span>
                                <div>
                                    <strong>{{ __('app.reviews_disliked') }}</strong>
                                    <p>{{ $review->dislikedTextForLocale(app()->getLocale()) }}</p>
                                </div>
                            </div>
                        @endif
                        <div class="review-card__author">
                            <div>
                                <p class="review-card__author-name">{{ $review->author_name }}</p>
                                @if($review->source)
                                    <p class="review-card__author-source">{{ __('app.reviews_source', ['source' => $review->source]) }}</p>
                                @endif
                                @if($review->booking)
                                    <p class="review-card__author-date">{{ $review->booking->checkout->format('M Y') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

@endsection
