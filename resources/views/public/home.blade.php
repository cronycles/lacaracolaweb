@extends('layouts.app')

@section('title', config('apartment.seo.' . app()->getLocale() . '.title'))
@section('description', config('apartment.seo.' . app()->getLocale() . '.description'))

@push('schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@type": "LodgingBusiness",
  "name": "{{ config('apartment.name') }}",
  "description": "@yield('description')",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "{{ config('apartment.address.street') }}",
    "addressLocality": "{{ config('apartment.address.city') }}",
    "addressRegion": "{{ config('apartment.address.province') }}",
    "postalCode": "{{ config('apartment.address.zip') }}",
    "addressCountry": "{{ config('apartment.address.country') }}"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": {{ config('apartment.address.lat') }},
    "longitude": {{ config('apartment.address.lng') }}
  },
  "url": "{{ url('/') }}",
  "numberOfRooms": {{ config('apartment.specs.bedrooms') }},
  "occupancy": { "@type": "QuantitativeValue", "maxValue": {{ config('apartment.specs.beds') }} }
}
</script>
@endpush

@section('content')

{{-- Hero with rotating slides --}}
<section class="hero" aria-label="Hero">
    <div class="hero-slider" aria-hidden="true">
        @foreach(config('apartment.images.hero') as $i => $heroPath)
        <div class="hero-slider__slide {{ $i === 0 ? 'active' : '' }}"
             style="background-image:url('{{ asset($heroPath) }}')"></div>
        @endforeach
    </div>

    <div class="hero__content">
        <p class="hero__eyebrow">{{ __('app.hero_eyebrow') }}</p>
        <h1 class="hero__title">{{ __('app.hero_title') }}</h1>
        <p class="hero__subtitle">{{ __('app.hero_subtitle') }}</p>
        <div class="hero__cta">
            <a href="{{ route('home') }}#booking" class="btn btn--accent btn--lg">
                {{ __('app.hero_cta_booking') }}
            </a>
            <a href="{{ route('apartment') }}" class="btn btn--ghost btn--lg">
                {{ __('app.hero_cta_discover') }}
            </a>
        </div>
    </div>

    {{-- Navigation dots --}}
    <div class="hero-dots" aria-label="Slide navigation">
        <button class="active" aria-label="Slide 1"></button>
        <button aria-label="Slide 2"></button>
        <button aria-label="Slide 3"></button>
    </div>
</section>

{{-- Feature highlights --}}
<section class="section" aria-label="Highlights">
    <div class="container">
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

{{-- Booking / Availability section --}}
<section class="section" id="booking" aria-labelledby="booking-title" style="background:var(--color-bg)">
    <div class="container">
        <h2 class="section-title" id="booking-title" style="text-align:center">{{ __('app.booking_title') }}</h2>

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
