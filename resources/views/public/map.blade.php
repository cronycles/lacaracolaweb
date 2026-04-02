@extends('layouts.app')

@section('title', __('app.map_title') . ' — ' . config('apartment.name'))

@push('head_css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endpush

@section('content')

<section class="section">
    <div class="container">
        <h1 class="section-title">{{ __('app.map_title') }}</h1>
        <p class="section-subtitle">{{ __('app.map_subtitle') }}</p>

        {{-- Interactive map --}}
        <div class="map-container">
            <div id="map"
                 data-lat="{{ config('apartment.address.lat') }}"
                 data-lng="{{ config('apartment.address.lng') }}"
                 data-name="{{ config('apartment.name') }}"
                 data-address="{{ config('apartment.address.street') }}, {{ config('apartment.address.city') }}">
            </div>
        </div>

        {{-- Navigate CTA --}}
        <div style="text-align:center;margin-top:1.5rem">
            <a href="#" id="map-navigate" class="btn btn--primary" target="_blank" rel="noopener noreferrer">
                📍 {{ __('app.map_navigate') }}
            </a>
        </div>

        {{-- Transport info --}}
        <h2 class="section-title" style="font-size:1.4rem;margin-top:3rem">{{ __('app.map_transport_title') }}</h2>
        <div class="transport-grid">
            <div class="transport-grid__item">
                <div class="transport-grid__item-icon">🚗</div>
                <div>
                    <p class="transport-grid__item-title">{{ __('app.map_car') }}</p>
                    <p class="transport-grid__item-text">{{ __('app.map_car_text') }}</p>
                </div>
            </div>
            <div class="transport-grid__item">
                <div class="transport-grid__item-icon">🚂</div>
                <div>
                    <p class="transport-grid__item-title">{{ __('app.map_train') }}</p>
                    <p class="transport-grid__item-text">{{ __('app.map_train_text') }}</p>
                </div>
            </div>
            <div class="transport-grid__item">
                <div class="transport-grid__item-icon">✈️</div>
                <div>
                    <p class="transport-grid__item-title">{{ __('app.map_plane') }}</p>
                    <p class="transport-grid__item-text">{{ __('app.map_plane_text') }}</p>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
