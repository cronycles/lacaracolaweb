@extends('layouts.app')

@section('title', __('app.nav_apartment') . ' — ' . config('apartment.name'))
@section('description', config('apartment.seo.' . app()->getLocale() . '.description'))

@section('content')

<section class="section">
    <div class="container">
        <p class="section-subtitle">{{ config('apartment.address.city') }}, {{ config('apartment.address.region') }}</p>
        <h1 class="section-title">{{ __('app.nav_apartment') }}</h1>

        {{-- Quick stats --}}
        <div style="display:flex;gap:2rem;flex-wrap:wrap;margin-bottom:3rem">
            <span>🛏️ <strong>{{ config('apartment.specs.beds') }}</strong> posti letto</span>
            <span>🚪 <strong>{{ config('apartment.specs.bedrooms') }}</strong> camere</span>
            <span>🚿 <strong>{{ config('apartment.specs.bathrooms') }}</strong> bagno</span>
            <span>🏡 Piano <strong>{{ config('apartment.specs.floor') }}</strong></span>
        </div>

        {{-- Amenities grid --}}
        <h2 class="section-title" style="font-size:1.4rem">Servizi inclusi</h2>
        <div class="apartment-amenities">
            @foreach(config('apartment.amenities') as $amenity)
            <div class="apartment-amenities__item">
                <span class="apartment-amenities__item-icon" aria-hidden="true">{{ $amenity['icon'] }}</span>
                {{ __('app.' . $amenity['key']) }}
            </div>
            @endforeach
        </div>

        {{-- Gallery — paths centralised in config/apartment.php images.gallery --}}
        <h2 class="section-title" style="font-size:1.4rem;margin-top:3rem">Galleria</h2>
        <div class="gallery">
            @foreach(config('apartment.images.gallery') as $i => $imgPath)
            @php $n = $i + 1; $exists = file_exists(public_path($imgPath)); @endphp
            <div class="gallery__item">
                <img src="{{ $exists ? asset($imgPath) : 'https://placehold.co/600x400?text=Foto+' . $n }}"
                     alt="{{ config('apartment.name') }} — foto {{ $n }}"
                     loading="{{ $i < 2 ? 'eager' : 'lazy' }}"
                     width="600" height="400">
                <div class="gallery__overlay" aria-hidden="true">🔍</div>
            </div>
            @endforeach
        </div>

        {{-- Lightbox --}}
        <div class="lightbox" role="dialog" aria-label="Foto a schermo intero" aria-modal="true">
            <img src="" alt="">
            <button class="lightbox__close" aria-label="Chiudi">×</button>
        </div>
    </div>
</section>

{{-- Booking CTA --}}
<section class="section" style="background:var(--color-bg);text-align:center">
    <div class="container">
        <h2 class="section-title">Interesse? Richiedi la disponibilità</h2>
        <p class="section-subtitle" style="margin-inline:auto">Rispondiamo entro 24 ore.</p>
        <a href="{{ route('home') }}#booking" class="btn btn--primary btn--lg">{{ __('app.hero_cta_booking') }}</a>
    </div>
</section>

@endsection
