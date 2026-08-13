{{-- Navigation component --}}
<nav class="nav {{ request()->routeIs('*.home') ? 'nav--overlay' : '' }}" aria-label="Main navigation">
    <div class="container nav__inner">

        <div class="nav__controls">
            {{-- Burger menu: shared navigation on mobile and desktop --}}
            <button class="nav__burger" aria-label="Menu" aria-expanded="false" aria-controls="mobile-menu">
                <span></span><span></span><span></span>
            </button>

            {{-- Language switcher dropdown --}}
            <div class="lang-dropdown lang-dropdown--desktop" id="langDropdown">
                <button class="lang-dropdown__toggle" aria-label="Select language" aria-expanded="false" aria-controls="lang-menu">
                    @php
                        $currentLocale = app()->getLocale();
                    @endphp
                    <span>{{ strtoupper($currentLocale) }}</span>
                </button>
                <ul class="lang-dropdown__menu" id="lang-menu" role="listbox">
                    @foreach(['it','en','fr','de'] as $locale)
                        <li>
                            <button type="button" data-lang="{{ $locale }}" class="lang-dropdown__item" role="option" aria-selected="{{ app()->getLocale() === $locale ? 'true' : 'false' }}">
                                <span>{{ strtoupper($locale) }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Logo: brand wordmark --}}
        <a href="{{ route_locale('home') }}" class="nav__logo" aria-label="{{ config('apartment.name') }}">
            <img src="{{ asset('images/brand/logo-wordmark-white.svg') }}"
                 alt=""
                 aria-hidden="true"
                 width="180"
                 height="36">
        </a>

        {{-- Booking CTA: visually separated button, not a plain nav link --}}
        <a href="{{ route_locale('home') }}#booking" class="btn btn--accent btn--sm nav__cta">{{ __('app.nav_booking') }}</a>

        {{-- Admin area link (discreet) --}}
        <!-- <a href="/admin" class="nav__admin-link" aria-label="Admin area" title="Admin">
            🔒
        </a> -->

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
    {{-- Admin area link (mobile) --}}
    <!-- <a href="/admin" class="nav-mobile__admin-link" aria-label="Admin area">🔒</a> -->
    {{-- Language switcher: flat buttons inside mobile menu --}}
    <div class="lang-switcher-mobile">
        @foreach(['it','en','fr','de'] as $locale)
            <button type="button" data-lang="{{ $locale }}" class="lang-switcher-mobile__btn" role="option"
                    aria-selected="{{ app()->getLocale() === $locale ? 'true' : 'false' }}">
                <span>{{ strtoupper($locale) }}</span>
            </button>
        @endforeach
    </div>
</nav>
