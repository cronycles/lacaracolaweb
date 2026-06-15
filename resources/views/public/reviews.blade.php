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
                    "Beautiful, bright and comfortable apartment in excellent location well served with shops, market, restaurants and bars, facing the sea and in the center of Andora in a quiet and safe place. Large and clean apartment, fully equipped with all comfort with a balcony and garden available. The reserved parking space and pet acceptance are really much appreciated. I highly recommend it for a stay in Andora. A special thanks to S. for her welcome and availability during our stay!"
                </p>
                <div class="review-card__author">
                    <div>
                        <p class="review-card__author-name">Luisella</p>
                        <p class="review-card__author-source">{{ __('app.reviews_source', ['source' => 'Interhome']) }}</p>
                    </div>
                </div>
            </div>
            <div class="review-card">
                <div class="review-card__stars">★★★★★</div>
                <p class="review-card__text">
                    "We were warmly welcomed by S. despite our slight delay. The apartment is wonderfully close to the beach and was well equipped and clean. Since we were there in the low season, parking was not a problem; otherwise, we were offered the option to book a parking space on site, which we would definitely recommend during the summer months. The fact that we were also asked in advance to take photos of our IDs, and that there was an additional cash deposit on site, was a little confusing at first, but in the end everything went very smoothly. We would definitely book the apartment again!"
                </p>
                <div class="review-card__author">
                    <div>
                        <p class="review-card__author-name">Franziska</p>
                        <p class="review-card__author-source">{{ __('app.reviews_source', ['source' => 'Interhome']) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
