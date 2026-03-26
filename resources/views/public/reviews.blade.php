@extends('layouts.app')

@section('title', __('app.reviews_title') . ' — ' . config('apartment.name'))

@section('content')

<section class="section">
    <div class="container">
        <h1 class="section-title" style="text-align:center">{{ __('app.reviews_title') }}</h1>
        <p class="section-subtitle" style="text-align:center;margin-inline:auto">{{ __('app.reviews_subtitle') }}</p>

        {{-- Reviews are managed manually in this MVP phase --}}
        <div class="reviews-grid">

            {{-- Example review card — replace/extend with DB-driven reviews in Phase 2 --}}
            <div class="review-card">
                <div class="review-card__stars">★★★★★</div>
                <p class="review-card__text">
                    "Appartamento perfetto, pulito e a due passi dalla spiaggia. Il balcone con vista mare
                    è semplicemente meraviglioso. Torneremo senz'altro!"
                </p>
                <div class="review-card__author">
                    <div>
                        <p class="review-card__author-name">Marco R.</p>
                        <p class="review-card__author-source">{{ __('app.reviews_source', ['source' => 'Airbnb']) }}</p>
                    </div>
                </div>
            </div>

            <div class="review-card">
                <div class="review-card__stars">★★★★★</div>
                <p class="review-card__text">
                    "Superb location, everything you need is within walking distance.
                    The apartment has a lovely sea view and a spacious garden."
                </p>
                <div class="review-card__author">
                    <div>
                        <p class="review-card__author-name">Sophie L.</p>
                        <p class="review-card__author-source">{{ __('app.reviews_source', ['source' => 'Booking.com']) }}</p>
                    </div>
                </div>
            </div>

            <div class="review-card">
                <div class="review-card__stars">★★★★★</div>
                <p class="review-card__text">
                    "Magnifique appartement face à la mer. La terrasse est idéale pour les repas en famille.
                    Andora est un village adorable, loin du tourisme de masse."
                </p>
                <div class="review-card__author">
                    <div>
                        <p class="review-card__author-name">Claire D.</p>
                        <p class="review-card__author-source">{{ __('app.reviews_source', ['source' => 'Airbnb']) }}</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

@endsection
