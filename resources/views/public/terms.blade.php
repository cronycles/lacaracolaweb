@extends('layouts.app')

@section('title', __('app.terms_title') . ' — ' . config('apartment.name'))

@section('content')

<section class="section qr-page">
    <div class="qr-page__header">
        <h1 class="section-title" style="text-align:center">{{ __('app.terms_title') }}</h1>
        <p style="text-align:center;color:var(--color-text-muted)">{{ __('app.terms_subtitle') }}</p>
    </div>

    <div class="rules-list rules-list--plain">
        @foreach(config('apartment.terms') as $section)
        <div class="rules-list__item">
            <div>
                <p class="rules-list__item-title">{{ __($section['title_key']) }}</p>
                <p class="rules-list__item-text">{!! nl2br(__($section['text_key'], ['rules_url' => route_locale('rules')])) !!}</p>
            </div>
        </div>
        @endforeach
    </div>
</section>

@endsection
