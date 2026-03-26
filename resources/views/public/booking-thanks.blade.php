@extends('layouts.app')

@section('title', __('app.booking_thanks_title') . ' — ' . config('apartment.name'))

@section('content')

<section class="section" style="text-align:center;min-height:60vh;display:flex;align-items:center">
    <div class="container">
        <div style="font-size:3rem;margin-bottom:1rem">✅</div>
        <h1 class="section-title">{{ __('app.booking_thanks_title') }}</h1>
        <p class="section-subtitle" style="margin-inline:auto">
            {{ __('app.booking_thanks_text') }}
        </p>
        <a href="{{ route('home') }}" class="btn btn--primary" style="margin-top:1rem">
            ← Torna alla Home
        </a>
    </div>
</section>

@endsection
