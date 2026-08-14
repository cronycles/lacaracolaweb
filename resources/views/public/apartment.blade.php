@extends('layouts.app')

@section('title', __('app.nav_apartment') . ' — ' . config('apartment.name'))
@section('description', config('apartment.seo.' . app()->getLocale() . '.description'))

@section('content')

<section class="section">
    <div class="container">
        <p class="section-subtitle">{{ config('apartment.address.city') }}, {{ config('apartment.address.region') }}</p>
        <h1 class="section-title">{{ __('app.nav_apartment') }}</h1>

        {{-- Lead gallery: the apartment should be visible before its details. --}}
        <div class="gallery gallery--lead">
            @foreach(config('apartment.images.gallery') as $i => $imgPath)
            @php $n = $i + 1; $exists = file_exists(public_path($imgPath)); @endphp
            <div class="gallery__item {{ $i === 0 ? 'gallery__item--featured' : '' }}">
                <img src="{{ $exists ? asset($imgPath) : 'https://placehold.co/600x400?text=Foto+' . $n }}"
                     alt="{{ config('apartment.name') }} — foto {{ $n }}"
                     loading="{{ $i < 2 ? 'eager' : 'lazy' }}"
                     width="600" height="400">
                <div class="gallery__overlay" aria-hidden="true">🔍</div>
            </div>
            @endforeach
        </div>

        <div class="apartment-intro">
            <div class="apartment-stats">
                <span>🛏️ <strong>{{ config('apartment.specs.beds') }}</strong> {{ __('app.apartment_beds_label') }}</span>
                <span>🚪 <strong>{{ config('apartment.specs.bedrooms') }}</strong> {{ __('app.apartment_bedrooms_label') }}</span>
                <span>🚿 <strong>{{ config('apartment.specs.bathrooms') }}</strong> {{ __('app.apartment_bathrooms_label') }}</span>
                <span>📐 <strong>{{ config('apartment.specs.sqm') }} m²</strong></span>
            </div>
        </div>

        @php
            $amenityGroups = [
                'apartment_amenities_comfort' => ['amenity_wifi', 'amenity_tv', 'amenity_mosquito_nets', 'amenity_washing_machine', 'amenity_pets_allowed', 'amenity_non_smoking_rooms'],
                'apartment_amenities_kitchen' => ['amenity_kitchen', 'amenity_dishwasher', 'amenity_coffee', 'amenity_kettle', 'amenity_toaster', 'amenity_microwave'],
                'apartment_amenities_outdoors' => ['amenity_sea_view', 'amenity_garden', 'amenity_balcony', 'amenity_private_parking_on_request'],
            ];
        @endphp

        {{-- Amenities grouped by the decision they help the guest make. --}}
        <div class="apartment-amenities">
            @foreach($amenityGroups as $titleKey => $keys)
            <section class="apartment-amenities__group" aria-labelledby="{{ $titleKey }}">
                <h2 class="apartment-amenities__title" id="{{ $titleKey }}">{{ __('app.' . $titleKey) }}</h2>
                <div class="apartment-amenities__grid">
                    @foreach(config('apartment.amenities') as $amenity)
                    @if(in_array($amenity['key'], $keys, true))
                    <div class="apartment-amenities__item">
                        <span class="apartment-amenities__item-icon" aria-hidden="true">{{ $amenity['icon'] }}</span>
                        {{ __('app.' . $amenity['key']) }}
                    </div>
                    @endif
                    @endforeach
                </div>
            </section>
            @endforeach
        </div>

        {{-- Lightbox --}}
        <div class="lightbox" role="dialog" aria-label="{{ __('app.apartment_gallery_fullscreen') }}" aria-modal="true">
            <div class="lightbox__topbar">
                <button class="lightbox__back" aria-label="{{ __('app.apartment_gallery_close') }}">×</button>
                <span class="lightbox__counter" aria-live="polite">1 / {{ count(config('apartment.images.gallery')) }}</span>
                <button class="lightbox__share" aria-label="Condividi il sito"><span aria-hidden="true">↥</span></button>
            </div>
            <button class="lightbox__nav lightbox__nav--prev" aria-label="{{ __('app.apartment_gallery_prev') }}">‹</button>
            <img src="" alt="">
            <button class="lightbox__nav lightbox__nav--next" aria-label="{{ __('app.apartment_gallery_next') }}">›</button>
            <button class="lightbox__close" aria-label="{{ __('app.apartment_gallery_close') }}">×</button>
        </div>
    </div>
</section>

{{-- Booking CTA --}}
<section class="section" style="background:var(--color-bg);text-align:center">
    <div class="container">
        <h2 class="section-title">{{ __('app.apartment_cta_title') }}</h2>
        <p class="section-subtitle" style="margin-inline:auto">{{ __('app.apartment_cta_subtitle') }}</p>
        <a href="{{ route_locale('home') }}#booking" class="btn btn--primary btn--lg">{{ __('app.hero_cta_booking') }}</a>
    </div>
</section>

@endsection
