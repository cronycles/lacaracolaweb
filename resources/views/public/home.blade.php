@extends('layouts.app')

@section('title', config('apartment.seo.' . app()->getLocale() . '.title'))
@section('description', config('apartment.seo.' . app()->getLocale() . '.description'))

@push('scripts')
<script>window.COUNTRIES_MAP = @json($countries);</script>
<script>window.COUNTRIES_DIAL = @json($countriesDial);</script>
@endpush

@push('schema')
        @include('components.schema-vacation-rental', ['apartment' => $apartment])
@endpush

@section('content')

{{-- Hero with a single static image --}}
<section class="hero" aria-label="Hero" style="background-image:url('{{ asset(config('apartment.images.hero')) }}')">

    <div class="hero__content">
        <p class="hero__eyebrow">{{ __('app.hero_eyebrow') }}</p>
        <h1 class="hero__title">{!! __('app.hero_title') !!}</h1>
        <p class="hero__subtitle">{{ __('app.hero_subtitle') }}</p>
        <div class="hero__cta">
            <a href="{{ route_locale('apartment') }}" class="hero__discover-link">
                {{ __('app.hero_cta_discover') }} <span aria-hidden="true">→</span>
            </a>
            <a href="{{ route_locale('home') }}#booking" class="btn btn--accent btn--lg">
                {{ __('app.hero_cta_booking') }}
            </a>
        </div>
    </div>

</section>

{{-- Trust badges: only relevant when the internal direct-booking form is active (not the external-platform flow) --}}
@unless ($bookingMode === 'external' && $bookingExternalUrl)
<section class="section home-trust-badges" aria-label="{{ __('app.trust_badges_title') }}">
    <div class="container">
        <h2 class="section-title" style="text-align:center">{{ __('app.trust_badges_title') }}</h2>
        <div class="home-features">
            @foreach(config('apartment.trust_badges') as $badge)
            <div class="home-features__item">
                <div class="home-features__item-icon" aria-hidden="true">{{ $badge['icon'] }}</div>
                <h3 class="home-features__item-title">{{ __('app.' . $badge['key']) }}</h3>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endunless

{{-- Booking / Availability section --}}
<section class="section" id="booking" aria-labelledby="booking-title" style="background:var(--color-bg)">
    <div class="container">
        <h2 class="section-title" id="booking-title" style="text-align:center">{{ __('app.booking_title') }}</h2>
        <h3 class="section-subtitle" style="text-align:center;font-weight:400;margin-inline:auto">{{ __('app.booking_subtitle') }}</h3>

        @if ($bookingMode === 'external' && $bookingExternalUrl)
            {{-- Flow C: external platform CTA --}}
            <div class="booking-form" style="text-align:center;padding-block:var(--space-12)">
                <p style="font-size:1.5rem;margin-bottom:var(--space-4)">🏠</p>
                <h3 style="font-family:var(--font-serif);font-size:1.35rem;color:var(--color-primary);margin-bottom:var(--space-4)">
                    {{ __('app.booking_external_title') }}
                </h3>
                <p style="color:var(--color-text-muted);max-width:400px;margin-inline:auto;margin-bottom:var(--space-8);line-height:1.7">
                    {{ __('app.booking_external_text') }}
                </p>
                <a href="{{ $bookingExternalUrl }}" target="_blank" rel="noopener noreferrer"
                   class="btn btn--accent btn--lg">
                    {{ __('app.booking_external_btn') }}
                </a>
            </div>
        @else
            @include('components.booking-form')
        @endif
    </div>
</section>

{{-- Apartment feature highlights --}}
<section class="section home-highlights" aria-label="{{ __('app.home_features_title') }}">
    <div class="container">
        <h2 class="section-title" style="text-align:center">{{ __('app.home_features_title') }}</h2>
        <div class="home-features">
            @foreach(config('apartment.features') as $feature)
            <div class="home-features__item">
                <div class="home-features__item-icon" aria-hidden="true">{{ $feature['icon'] }}</div>
                <h3 class="home-features__item-title">{{ __('app.' . $feature['key']) }}</h3>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- SEO text block — localised for all 4 languages --}}
<section class="seo-section" aria-label="{{ __('app.seo_home_h2') }}">
    <div class="container seo-section__content">
        <h2>{{ __('app.seo_home_h2') }}</h2>
        <p>{!! __('app.seo_home_p1') !!}</p>
        <h3>{{ __('app.seo_home_h3') }}</h3>
        <p>{!! __('app.seo_home_p2') !!}</p>
    </div>
</section>

@endsection
