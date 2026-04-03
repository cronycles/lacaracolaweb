@extends('layouts.app')

@section('title', __('app.rules_title') . ' — ' . config('apartment.name'))

@section('content')

<section class="section qr-page">
    <div class="qr-page__header">
        <h1 class="section-title" style="text-align:center">{{ __('app.rules_title') }}</h1>
        <p style="text-align:center;color:var(--color-text-muted)">{{ __('app.rules_subtitle') }}</p>
    </div>

    <div class="rules-list">
        @foreach(config('apartment.rules') as $rule)
        <div class="rules-list__item">
            <div class="rules-list__item-icon" aria-hidden="true">{{ $rule['icon'] }}</div>
            <div>
                @if(!empty($rule['title_key']))
                    <p class="rules-list__item-title">{{ __($rule['title_key']) }}</p>
                @endif
                <p class="rules-list__item-text">{!! nl2br(__($rule['text_key'], config('apartment.rules_values'))) !!}</p>
            </div>
        </div>
        @endforeach
    </div>
</section>

@endsection
