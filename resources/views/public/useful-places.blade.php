@extends('layouts.app')

@section('title', __('app.nav_useful') . ' — ' . config('apartment.name'))

@section('content')

<section class="section qr-page">
    <div class="qr-page__header">
        <h1 class="section-title" style="text-align:center">{{ __('app.places_title') }}</h1>
        <p style="text-align:center;color:var(--color-text-muted)">{{ __('app.places_subtitle') }}</p>
    </div>

    {{-- Supermarkets --}}
    <h2 class="section-title" style="font-size:1.3rem;margin-top:2rem">
        🛒 {{ __('app.places_supermarkets') }}
    </h2>
    <div class="card-grid">
        @foreach(config('apartment.useful_places.supermarkets') as $place)
        <div class="card">
            <div class="card__body">
                <p class="card__title">{{ $place['name'] }}</p>
                <p class="card__text">📍 {{ $place['address'] }}</p>
                <p class="card__text">⏱ {{ $place['distance'] }}</p>
                @if(isset($place['note_key']))
                    <p class="card__text" style="font-style:italic;margin-top:.5rem">
                        ℹ️ {{ __('app.' . $place['note_key']) }}
                    </p>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Restaurants --}}
    <h2 class="section-title" style="font-size:1.3rem;margin-top:3rem">
        🍽️ {{ __('app.places_restaurants') }}
    </h2>
    <div class="card-grid">
        @foreach(config('apartment.useful_places.restaurants') as $place)
        <div class="card">
            <div class="card__body">
                <p class="card__title">{{ $place['name'] }}</p>
                <p class="card__text">📍 {{ $place['address'] }}</p>
                <p class="card__text">⏱ {{ $place['distance'] }}</p>
                @if(isset($place['desc_key']))
                    <p class="card__text">{{ __('app.' . $place['desc_key']) }}</p>
                @endif
                @if(isset($place['note_key']))
                    <p class="card__text" style="font-style:italic;margin-top:.5rem">
                        ℹ️ {{ __('app.' . $place['note_key']) }}
                    </p>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</section>

@endsection
