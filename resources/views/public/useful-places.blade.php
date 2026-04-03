@extends('layouts.app')

@section('title', __('app.nav_useful') . ' — ' . config('apartment.name'))

@section('content')

<section class="section">
    <div class="container">
        <div style="text-align:center;margin-bottom:var(--space-8)">
            <h1 class="section-title">{{ __('app.places_title') }}</h1>
            <p style="color:var(--color-text-muted)">{{ __('app.places_subtitle') }}</p>
        </div>

        @foreach(config('apartment.useful_places') as $category => $data)
            <h2 class="section-title" style="font-size:1.4rem;margin-top:var(--space-12);margin-bottom:var(--space-6)">
                {{ $data['icon'] }} {{ __('app.places_' . $category) }}
            </h2>

            <div class="card-grid" style="margin-bottom:var(--space-12)">
                @foreach($data['places'] as $place)
                    @php
                        $mapsUrl = $place['maps_url'] ?? null;
                        $isPhoneLink = is_string($mapsUrl) && str_starts_with($mapsUrl, 'tel:');
                        $phoneNumber = $isPhoneLink ? substr($mapsUrl, 4) : null;
                    @endphp
                    <div class="card">
                        <div class="card__body">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:var(--space-3);margin-bottom:var(--space-4)">
                                <h3 class="card__title" style="margin:0;flex:1">{{ $place['name'] }}</h3>
                                @if($mapsUrl && !$isPhoneLink)
                                    <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer"
                                       style="display:inline-flex;align-items:center;gap:var(--space-2);font-size:.82rem;font-weight:600;color:var(--color-primary);text-decoration:none;padding:var(--space-2) var(--space-3);border:1px solid var(--color-border);border-radius:var(--radius-full);background:var(--color-bg)"
                                       title="{{ __('app.places_open_maps') }}">
                                        🗺️ {{ __('app.places_open_maps') }}
                                    </a>
                                @endif
                            </div>

                            @if(isset($place['address']))
                                <p class="card__text">📍 {{ $place['address'] }}</p>
                            @endif

                            @if(isset($place['distance']))
                                <p class="card__text">⏱️ {{ $place['distance'] }}</p>
                            @endif

                            @if($isPhoneLink && $phoneNumber)
                                <p class="card__text">📞 <a href="{{ $mapsUrl }}" style="font-weight:700;color:var(--color-primary);text-decoration:underline">{{ $phoneNumber }}</a></p>
                            @endif

                            @if(isset($place['desc_key']))
                                <p class="card__text" style="margin-top:var(--space-3);padding:var(--space-3);background:var(--color-bg);border-left:3px solid var(--color-primary-light);border-radius:var(--radius-sm);font-size:.92rem;color:var(--color-text-secondary)">
                                    ✍️ {{ __('app.' . $place['desc_key']) }}
                                </p>
                            @endif

                            @if(isset($place['note_key']))
                                <p class="card__text" style="margin-top:var(--space-3);padding:var(--space-3);background:var(--color-bg);border-left:3px solid var(--color-accent);border-radius:var(--radius-sm);font-style:italic;color:var(--color-text-muted);font-size:.9rem">
                                    ℹ️ {{ __('app.' . $place['note_key']) }}
                                </p>
                            @endif

                            @if(isset($place['advice_key']))
                                <p class="card__text" style="margin-top:var(--space-3);padding:var(--space-3);background:var(--color-bg);border-left:3px solid var(--color-primary);border-radius:var(--radius-sm);font-size:.9rem;color:var(--color-text-secondary)">
                                    <strong>💡 {{ __('app.places_advice') }}:</strong> {!! __('app.' . $place['advice_key']) !!}
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</section>

@endsection
