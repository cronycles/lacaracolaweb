@extends('layouts.app')

@section('title', __('app.checkin_expired_title') . ' — ' . config('apartment.name'))

@section('content')

<section class="section">
    <div class="container" style="max-width:640px;text-align:center">
        <h1 class="section-title">{{ __('app.checkin_expired_title') }}</h1>
        <p style="color:var(--color-text-muted)">{{ __('app.checkin_expired_text') }}</p>
        <a href="{{ route_locale('home') }}" class="btn btn--primary" style="margin-top:var(--space-6)">{{ __('app.checkin_expired_home_link') }}</a>
    </div>
</section>

@endsection
