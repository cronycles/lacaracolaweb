{{-- Footer component --}}
<footer class="footer">
    <div class="container footer__grid">

        {{-- Brand column --}}
        <div>
            <p class="footer__brand">{{ config('apartment.name') }}</p>
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
                <li><a href="{{ route('apartment') }}">{{ __('app.nav_apartment') }}</a></li>
                <li><a href="{{ route('map') }}">{{ __('app.nav_map') }}</a></li>
                <li><a href="{{ route('experiences') }}">{{ __('app.nav_experiences') }}</a></li>
                <li><a href="{{ route('reviews') }}">{{ __('app.nav_reviews') }}</a></li>
                <li><a href="{{ route('rules') }}">{{ __('app.nav_rules') }}</a></li>
                <li><a href="{{ route('useful-places') }}">{{ __('app.nav_useful') }}</a></li>
            </ul>
        </div>

        {{-- Contact --}}
        <div>
            <p class="footer__heading">{{ __('app.footer_contact') }}</p>
            <ul class="footer__links" role="list">
                <li><a href="{{ route('booking.thanks') }}">{{ __('app.nav_booking') }}</a></li>
                @if(config('apartment.platforms.airbnb'))
                    <li><a href="{{ config('apartment.platforms.airbnb') }}" target="_blank" rel="noopener">Airbnb</a></li>
                @endif
                @if(config('apartment.platforms.booking'))
                    <li><a href="{{ config('apartment.platforms.booking') }}" target="_blank" rel="noopener">Booking.com</a></li>
                @endif
            </ul>
        </div>

    </div>

    <div class="container footer__bottom">
        {{ __('app.footer_legal', ['year' => date('Y')]) }}
    </div>
</footer>
