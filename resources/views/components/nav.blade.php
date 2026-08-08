{{-- Navigation component --}}
<nav class="nav" aria-label="Main navigation">
    <div class="container nav__inner">

        {{-- Logo: brand symbol + name --}}
        <a href="{{ route_locale('home') }}" class="nav__logo" aria-label="{{ config('apartment.name') }}">
            <img src="{{ asset('images/brand/logo-symbol-blue.svg') }}"
                 alt=""
                 aria-hidden="true"
                 width="54"
                 height="36">
            <span class="nav__logo-name">{{ config('apartment.name') }}</span>
        </a>

        {{-- Desktop links --}}
        <ul class="nav__links" role="list">
            <li><a href="{{ route_locale('home') }}"         class="nav__link @active('home')">{{ __('app.nav_home') }}</a></li>
            <li><a href="{{ route_locale('apartment') }}"    class="nav__link @active('apartment')">{{ __('app.nav_apartment') }}</a></li>
            <li><a href="{{ route_locale('map') }}"          class="nav__link @active('map')">{{ __('app.nav_map') }}</a></li>
            <li><a href="{{ route_locale('experiences') }}"  class="nav__link @active('experiences')">{{ __('app.nav_experiences') }}</a></li>
            <li><a href="{{ route_locale('reviews') }}"      class="nav__link @active('reviews')">{{ __('app.nav_reviews') }}</a></li>
        </ul>

        {{-- Booking CTA: visually separated button, not a plain nav link --}}
        <a href="{{ route_locale('home') }}#booking" class="btn btn--accent btn--sm nav__cta">{{ __('app.nav_booking') }}</a>

        {{-- Language switcher dropdown --}}
        <div class="lang-dropdown lang-dropdown--desktop" id="langDropdown">
            <button class="lang-dropdown__toggle" aria-label="Select language" aria-expanded="false" aria-controls="lang-menu">
                @php
                    $flagPaths = [
                        'it' => 'images/flags/it.svg',
                        'en' => 'images/flags/en.svg',
                        'fr' => 'images/flags/fr.svg',
                        'de' => 'images/flags/de.svg',
                    ];
                    $currentLocale = app()->getLocale();
                @endphp
                <img src="{{ asset($flagPaths[$currentLocale] ?? $flagPaths['it']) }}" alt="" width="24" height="18" aria-hidden="true">
            </button>
            <ul class="lang-dropdown__menu" id="lang-menu" role="listbox">
                @foreach(['it','en','fr','de'] as $locale)
                    <li>
                        <button type="button" data-lang="{{ $locale }}" class="lang-dropdown__item" role="option" aria-selected="{{ app()->getLocale() === $locale ? 'true' : 'false' }}">
                            <img src="{{ asset($flagPaths[$locale]) }}" alt="" width="24" height="18" aria-hidden="true">
                            <span>{{ strtoupper($locale) }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Admin area link (discreet) --}}
        <!-- <a href="/admin" class="nav__admin-link" aria-label="Admin area" title="Admin">
            🔒
        </a> -->

        {{-- Mobile burger --}}
        <button class="nav__burger" aria-label="Menu" aria-expanded="false" aria-controls="mobile-menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

{{-- Mobile menu --}}
<nav class="nav-mobile" id="mobile-menu" aria-label="Mobile navigation">
    <button class="nav-mobile__close" aria-label="{{ __('app.apartment_gallery_close') }}">&#x2715;</button>
    <a href="{{ route_locale('home') }}">{{ __('app.nav_home') }}</a>
    <a href="{{ route_locale('apartment') }}">{{ __('app.nav_apartment') }}</a>
    <a href="{{ route_locale('map') }}">{{ __('app.nav_map') }}</a>
    <a href="{{ route_locale('experiences') }}">{{ __('app.nav_experiences') }}</a>
    <a href="{{ route_locale('reviews') }}">{{ __('app.nav_reviews') }}</a>
    <a href="{{ route_locale('home') }}#booking" class="nav-mobile__cta">{{ __('app.nav_booking') }}</a>
    <a href="{{ route_locale('rules') }}">{{ __('app.nav_rules') }}</a>
    <a href="{{ route_locale('useful-places') }}">{{ __('app.nav_useful') }}</a>
    {{-- Admin area link (mobile) --}}
    <!-- <a href="/admin" class="nav-mobile__admin-link" aria-label="Admin area">🔒</a> -->
    {{-- Language switcher: flat buttons inside mobile menu --}}
    <div class="lang-switcher-mobile">
        @foreach(['it','en','fr','de'] as $locale)
            <button type="button" data-lang="{{ $locale }}" class="lang-switcher-mobile__btn" role="option"
                    aria-selected="{{ app()->getLocale() === $locale ? 'true' : 'false' }}">
                <img src="{{ asset($flagPaths[$locale]) }}" alt="" width="24" height="18" aria-hidden="true">
                <span>{{ strtoupper($locale) }}</span>
            </button>
        @endforeach
    </div>
</nav>
