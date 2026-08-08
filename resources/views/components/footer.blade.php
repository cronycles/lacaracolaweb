{{-- Footer component --}}
<footer class="footer">
    <div class="container footer__grid">

        {{-- Brand column --}}
        <div>
            <p class="footer__brand">
                <img src="{{ asset('images/brand/logo-symbol-gold.svg') }}"
                     alt=""
                     aria-hidden="true"
                     width="32"
                     height="32">
                <span>{{ config('apartment.name') }}</span>
            </p>
            <p class="footer__desc">{{ __('app.footer_desc') }}</p>
            <p style="margin-top:.75rem;font-size:.85rem;opacity:.6">
                {{ config('apartment.address.street') }}<br>
                {{ config('apartment.address.zip') }} {{ config('apartment.address.city') }} ({{ config('apartment.address.province') }})
            </p>
        </div>

        {{-- Quick links --}}
        <div>
            <p class="footer__heading">{{ __('app.footer_links') }}</p>
            <ul class="footer__links" role="list">
                <li><a href="{{ route_locale('apartment') }}">{{ __('app.nav_apartment') }}</a></li>
                <li><a href="{{ route_locale('map') }}">{{ __('app.nav_map') }}</a></li>
                <li><a href="{{ route_locale('experiences') }}">{{ __('app.nav_experiences') }}</a></li>
                <li><a href="{{ route_locale('reviews') }}">{{ __('app.nav_reviews') }}</a></li>
                <li><a href="{{ route_locale('rules') }}">{{ __('app.nav_rules') }}</a></li>
                <li><a href="{{ route_locale('terms') }}">{{ __('app.nav_terms') }}</a></li>
                <li><a href="{{ route_locale('useful-places') }}">{{ __('app.nav_useful') }}</a></li>
            </ul>
        </div>

        {{-- Book --}}
        <div>
            <p class="footer__heading">{{ __('app.footer_book') }}</p>
            <ul class="footer__links" role="list">
                <li><a href="{{ route_locale('home') }}#booking">{{ __('app.nav_booking') }}</a></li>
                @if(config('apartment.platforms.airbnb'))
                    <li><a href="{{ config('apartment.platforms.airbnb') }}" target="_blank" rel="noopener">Airbnb</a></li>
                @endif
                @if(config('apartment.platforms.booking'))
                    <li><a href="{{ config('apartment.platforms.booking') }}" target="_blank" rel="noopener">Booking.com</a></li>
                @endif
            </ul>
        </div>

        {{-- Contact --}}
        <div>
            <p class="footer__heading">{{ __('app.footer_contact') }}</p>
            <ul class="footer__links" role="list">
                <li><a href="{{ route_locale('contact') }}">{{ __('app.nav_contact') }}</a></li>
                <li><a href="{{ route_locale('home') }}#booking">{{ __('app.nav_booking') }}</a></li>
            </ul>
        </div>

    </div>

    <div class="container footer__bottom">
        {{ __('app.footer_legal', ['year' => date('Y')]) }}
    </div>
</footer>
