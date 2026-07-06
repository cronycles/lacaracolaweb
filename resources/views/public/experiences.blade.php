@extends('layouts.app')

@section('title', __('app.experiences_title') . ' — ' . config('apartment.name'))

@section('content')

<section class="section">
    <div class="container">
        <h1 class="section-title">{{ __('app.experiences_title') }}</h1>
        <p class="section-subtitle">{{ __('app.experiences_subtitle') }}</p>

        <div class="experiences-grid">

            <div class="card">
                <div class="card__body">
                    <p class="card__label">🏖️ {{ __('app.experience_1_time') }}</p>
                    <h3 class="card__title">{{ __('app.experience_1_title') }}</h3>
                    <p class="card__text">{{ __('app.experience_1_desc') }}</p>
                </div>
                <div class="card__footer">
                    <a href="https://maps.google.com/?q=Spiagge+Marina+di+Andora" target="_blank" rel="noopener noreferrer" class="card__maps-link">📍 {{ __('app.maps_cta') }}</a>
                </div>
            </div>

            <div class="card">
                <div class="card__body">
                    <p class="card__label">🚲 {{ __('app.experience_2_time') }}</p>
                    <h3 class="card__title">{{ __('app.experience_2_title') }}</h3>
                    <p class="card__text">{{ __('app.experience_2_desc') }}</p>
                </div>
                <div class="card__footer">
                    <a href="https://maps.app.goo.gl/tywKfEJWCdeZSyZN8" target="_blank" rel="noopener noreferrer" class="card__maps-link">📍 {{ __('app.maps_cta') }}</a>
                </div>
            </div>

            <div class="card">
                <div class="card__body">
                    <p class="card__label">🏘️ {{ __('app.experience_3_time') }}</p>
                    <h3 class="card__title">{{ __('app.experience_3_title') }}</h3>
                    <p class="card__text">{{ __('app.experience_3_desc') }}</p>
                </div>
                <div class="card__footer">
                    <a href="https://maps.google.com/?q=Cervo,+IM,+Liguria" target="_blank" rel="noopener noreferrer" class="card__maps-link">📍 {{ __('app.maps_cta') }}</a>
                </div>
            </div>

            <div class="card">
                <div class="card__body">
                    <p class="card__label">🌊 {{ __('app.experience_4_time') }}</p>
                    <h3 class="card__title">{{ __('app.experience_4_title') }}</h3>
                    <p class="card__text">{{ __('app.experience_4_desc') }}</p>
                </div>
                <div class="card__footer">
                    <a href="https://maps.google.com/?q=Alassio,+SV,+Liguria" target="_blank" rel="noopener noreferrer" class="card__maps-link">📍 {{ __('app.maps_cta') }}</a>
                </div>
            </div>

            <div class="card">
                <div class="card__body">
                    <p class="card__label">🏰 {{ __('app.experience_5_time') }}</p>
                    <h3 class="card__title">{{ __('app.experience_5_title') }}</h3>
                    <p class="card__text">{{ __('app.experience_5_desc') }}</p>
                </div>
                <div class="card__footer">
                    <a href="https://maps.google.com/?q=Finalborgo,+Finale+Ligure,+SV" target="_blank" rel="noopener noreferrer" class="card__maps-link">📍 {{ __('app.maps_cta') }}</a>
                </div>
            </div>

            <div class="card">
                <div class="card__body">
                    <p class="card__label">🎢 {{ __('app.experience_6_time') }}</p>
                    <h3 class="card__title">{{ __('app.experience_6_title') }}</h3>
                    <p class="card__text">{{ __('app.experience_6_desc') }}</p>
                </div>
                <div class="card__footer">
                    <a href="https://maps.google.com/?q=Parco+Acquatico+Le+Caravelle+Albenga" target="_blank" rel="noopener noreferrer" class="card__maps-link">📍 {{ __('app.maps_cta') }}</a>
                </div>
            </div>

            <div class="card">
                <div class="card__body">
                    <p class="card__label">🎲 {{ __('app.experience_7_time') }}</p>
                    <h3 class="card__title">{{ __('app.experience_7_title') }}</h3>
                    <p class="card__text">{{ __('app.experience_7_desc') }}</p>
                </div>
                <div class="card__footer">
                    <a href="https://maps.google.com/?q=Monte+Carlo,+Monaco" target="_blank" rel="noopener noreferrer" class="card__maps-link">📍 {{ __('app.maps_cta') }}</a>
                </div>
            </div>

            <div class="card">
                <div class="card__body">
                    <p class="card__label">🌸 {{ __('app.experience_8_time') }}</p>
                    <h3 class="card__title">{{ __('app.experience_8_title') }}</h3>
                    <p class="card__text">{{ __('app.experience_8_desc') }}</p>
                </div>
                <div class="card__footer">
                    <a href="https://maps.google.com/?q=Sanremo,+IM,+Liguria" target="_blank" rel="noopener noreferrer" class="card__maps-link">📍 {{ __('app.maps_cta') }}</a>
                </div>
            </div>

            <div class="card">
                <div class="card__body">
                    <p class="card__label">🇫🇷 {{ __('app.experience_9_time') }}</p>
                    <h3 class="card__title">{{ __('app.experience_9_title') }}</h3>
                    <p class="card__text">{{ __('app.experience_9_desc') }}</p>
                </div>
                <div class="card__footer">
                    <a href="https://maps.google.com/?q=Nice,+France" target="_blank" rel="noopener noreferrer" class="card__maps-link">📍 {{ __('app.maps_cta') }}</a>
                </div>
            </div>

            <div class="card">
                <div class="card__body">
                    <p class="card__label">🧗 Borghi</p>
                    <h3 class="card__title">{{ __('app.experience_10_title') }}</h3>
                    <p class="card__text">{{ __('app.experience_10_desc') }}</p>
                </div>
                <div class="card__footer">
                    <a href="https://maps.google.com/?q=Triora,+IM,+Liguria" target="_blank" rel="noopener noreferrer" class="card__maps-link">📍 {{ __('app.maps_cta') }}</a>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- SEO rich text about the area --}}
<section class="seo-section" aria-label="SEO area">
    <div class="container seo-section__content">
        <h2>{{ __('app.experiences_seo_h2') }}</h2>
        <p>{!! __('app.experiences_seo_p1') !!}</p>
        <h3>{{ __('app.experiences_seo_h3') }}</h3>
        <p>{!! __('app.experiences_seo_p2') !!}</p>
    </div>
</section>

@endsection
