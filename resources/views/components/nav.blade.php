{{-- Navigation component --}}
<nav class="nav" aria-label="Main navigation">
    <div class="container nav__inner">

        {{-- Logo --}}
        <a href="{{ route_locale('home') }}" class="nav__logo" aria-label="{{ config('apartment.name') }}">
            <span>{{ config('apartment.name') }}</span>
        </a>

        {{-- Desktop links --}}
        <ul class="nav__links" role="list">
            <li><a href="{{ route_locale('home') }}"         class="nav__link @active('home')">{{ __('app.nav_home') }}</a></li>
            <li><a href="{{ route_locale('apartment') }}"    class="nav__link @active('apartment')">{{ __('app.nav_apartment') }}</a></li>
            <li><a href="{{ route_locale('map') }}"          class="nav__link @active('map')">{{ __('app.nav_map') }}</a></li>
            <li><a href="{{ route_locale('experiences') }}"  class="nav__link @active('experiences')">{{ __('app.nav_experiences') }}</a></li>
            <li><a href="{{ route_locale('reviews') }}"      class="nav__link @active('reviews')">{{ __('app.nav_reviews') }}</a></li>
            <li><a href="{{ route_locale('home') }}#booking" class="nav__link @active('home')">{{ __('app.nav_booking') }}</a></li>
        </ul>

        {{-- Language switcher --}}
        <div class="nav__lang" aria-label="Language">
            @foreach(['it','en','fr','de'] as $locale)
                <button data-lang="{{ $locale }}"
                        class="{{ app()->getLocale() === $locale ? 'active' : '' }}"
                        aria-label="{{ strtoupper($locale) }}">
                    {{ strtoupper($locale) }}
                </button>
            @endforeach
        </div>

        {{-- Mobile burger --}}
        <button class="nav__burger" aria-label="Menu" aria-expanded="false" aria-controls="mobile-menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

{{-- Mobile menu --}}
<nav class="nav-mobile" id="mobile-menu" aria-label="Mobile navigation">
    <a href="{{ route_locale('home') }}">{{ __('app.nav_home') }}</a>
    <a href="{{ route_locale('apartment') }}">{{ __('app.nav_apartment') }}</a>
    <a href="{{ route_locale('map') }}">{{ __('app.nav_map') }}</a>
    <a href="{{ route_locale('experiences') }}">{{ __('app.nav_experiences') }}</a>
    <a href="{{ route_locale('reviews') }}">{{ __('app.nav_reviews') }}</a>
    <a href="{{ route_locale('home') }}#booking">{{ __('app.nav_booking') }}</a>
    <a href="{{ route_locale('rules') }}">{{ __('app.nav_rules') }}</a>
    <a href="{{ route_locale('useful-places') }}">{{ __('app.nav_useful') }}</a>
    {{-- Language buttons inside mobile menu --}}
    <div class="nav__lang" style="margin-top:1rem">
        @foreach(['it','en','fr','de'] as $locale)
            <button data-lang="{{ $locale }}" class="{{ app()->getLocale() === $locale ? 'active' : '' }}">
                {{ strtoupper($locale) }}
            </button>
        @endforeach
    </div>
</nav>
