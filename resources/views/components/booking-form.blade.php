{{-- Booking availability request form component --}}
{{-- Shared between home page (embedded) and standalone booking page --}}
{{-- Requires $countries/$countriesDial (phone prefix picker) pushed by the parent view --}}

{{-- Success message: hidden by default, shown in-place by JS after successful AJAX submission --}}
<div id="booking-success" class="booking-form booking-form--success" hidden>
    <div class="booking-form__success-icon" aria-hidden="true">✓</div>
    <h3>{{ __('app.booking_thanks_title') }}</h3>
    <p>{{ __('app.booking_thanks_text') }}</p>
</div>

@php($minNights = (int) \App\Models\Setting::get('pricing_min_nights', (string) config('apartment.booking.min_nights', 3)))
<form id="booking-form"
      method="POST"
      action="{{ route_locale('booking.request') }}"
      class="booking-form"
    data-quote-url="{{ route_locale('booking.quote') }}"
    data-locale="{{ app()->getLocale() }}"
      data-min-nights="{{ $minNights }}"
        data-min-booking-lead-days="{{ config('apartment.booking.min_booking_lead_days', 0) }}"
    data-max-beds="{{ config('apartment.specs.beds', 6) }}"
            data-error-lead-time="{{ __('app.error_checkin_lead_time', ['days' => config('apartment.booking.min_booking_lead_days', 0)]) }}"
      data-error-order="{{ __('app.error_checkout_order') }}"
      data-error-min-nights="{{ __('app.error_min_nights', ['nights' => $minNights]) }}"
    data-error-max-guests="{{ __('app.error_max_guests', ['guests' => config('apartment.specs.beds', 6)]) }}"
      data-error-server="{{ __('app.error_server') }}"
      data-label-loading="{{ __('app.booking_loading') }}"
    data-price-loading="{{ __('app.booking_price_loading') }}"
    data-parking-price-label="{{ __('app.booking_parking_price') }}"
    data-parking-total-label="{{ __('app.booking_parking_total') }}"
    data-parking-fee-cents="{{ (int) config('apartment.booking.parking_fee_per_day', 0) * 100 }}"
    data-unavailable-dates='@json($unavailableDates ?? [])'
      novalidate>
    @csrf

    {{-- Honeypot: must remain empty — filled only by bots --}}
    <input type="text" name="website" tabindex="-1" autocomplete="off"
           style="position:absolute;opacity:0;height:0;width:0;pointer-events:none" aria-hidden="true">

    {{-- Date range picker --}}
    <div class="date-picker" id="date-range-picker"
         data-locale="{{ app()->getLocale() }}"
         data-min-nights="{{ $minNights }}"
         data-min-booking-lead-days="{{ config('apartment.booking.min_booking_lead_days', 0) }}"
         data-hint-checkin="{{ __('app.booking_dp_hint_checkin') }}"
         data-hint-checkout="{{ __('app.booking_dp_hint_checkout', ['nights' => $minNights]) }}"
            data-label-clear="{{ __('app.booking_dp_clear') }}"
            data-legend-available="{{ __('app.booking_dp_legend_available') }}"
            data-legend-selected="{{ __('app.booking_dp_legend_selected') }}"
            data-legend-blocked="{{ __('app.booking_dp_legend_blocked') }}">

        <div class="date-picker__triggers">
            <div class="date-picker__field">
                <span class="date-picker__label">{{ __('app.booking_checkin') }} *</span>
                <button type="button" id="dp-trigger-checkin" class="dp-trigger" aria-haspopup="true">
                    <span class="dp-trigger__icon" aria-hidden="true">🗓</span>
                    <span class="dp-trigger__value" data-placeholder="{{ __('app.booking_checkin_placeholder') }}">{{ __('app.booking_checkin_placeholder') }}</span>
                </button>
                <input type="hidden" id="checkin" name="checkin">
                <span class="booking-form__field-error" data-error-for="checkin" hidden aria-live="polite"></span>
            </div>
            <div class="date-picker__field">
                <span class="date-picker__label">{{ __('app.booking_checkout') }} *</span>
                <button type="button" id="dp-trigger-checkout" class="dp-trigger" aria-haspopup="true">
                    <span class="dp-trigger__icon" aria-hidden="true">🗓</span>
                    <span class="dp-trigger__value" data-placeholder="{{ __('app.booking_checkout_placeholder') }}">{{ __('app.booking_checkout_placeholder') }}</span>
                </button>
                <input type="hidden" id="checkout" name="checkout">
                <span class="booking-form__field-error" data-error-for="checkout" hidden aria-live="polite"></span>
            </div>
        </div>

        {{-- Calendar popup (rendered by JS) --}}
        <div id="dp-popup" class="dp-popup" hidden role="dialog" aria-label="Calendar"></div>
    </div>

    <div class="booking-form__price" data-price-box hidden aria-live="polite">
        <p class="booking-form__price-title">{{ __('app.booking_price_title') }}</p>
        <p class="booking-form__price-value" data-price-value>—</p>
        <p class="booking-form__price-detail" data-price-detail></p>
        <p class="booking-form__price-detail" data-parking-total-detail hidden></p>
    </div>

    {{-- Guests row --}}
    <div class="booking-form__row booking-form__row--compact-mobile">
        <div class="booking-form__group">
            <label for="adults">{{ __('app.booking_adults') }} *</label>
            <select id="adults" name="adults" required>
                @foreach (range(1, 6) as $i)
                    <option value="{{ $i }}" {{ $i === 2 ? 'selected' : '' }}>{{ $i }}</option>
                @endforeach
            </select>
        </div>
        <div class="booking-form__group">
            <label for="children">{{ __('app.booking_children') }}</label>
            <select id="children" name="children">
                @foreach (range(0, 5) as $i)
                    <option value="{{ $i }}" {{ $i === 0 ? 'selected' : '' }}>{{ $i }}</option>
                @endforeach
            </select>
            <span class="booking-form__field-error" data-error-for="children" hidden aria-live="polite"></span>
        </div>
    </div>

    <div class="booking-form__row booking-form__row--compact-mobile">
        <div class="booking-form__group">
            <label for="babies">👶 {{ __('app.booking_babies') }} <span class="booking-form__hint">({{ __('app.booking_babies_hint') }})</span></label>
            <select id="babies" name="babies">
                @foreach (range(0, 3) as $i)
                    <option value="{{ $i }}" {{ $i === 0 ? 'selected' : '' }}>{{ $i }}</option>
                @endforeach
            </select>
        </div>
        <div class="booking-form__group">
            <label for="pets">🐾 {{ __('app.booking_pets') }}</label>
            <select id="pets" name="pets">
                @foreach (range(0, 3) as $i)
                    <option value="{{ $i }}" {{ $i === 0 ? 'selected' : '' }}>{{ $i }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="booking-form__newsletter">
        <span>
            <label for="parking_requested">🅿️ {{ __('app.booking_parking_requested') }}</label>
            <small data-parking-price hidden></small>
        </span>
        <label class="toggle" aria-label="{{ __('app.booking_parking_requested') }}">
            <input type="checkbox" id="parking_requested" name="parking_requested" value="1">
            <span class="toggle__track"><span class="toggle__thumb"></span></span>
        </label>
    </div>

    {{-- Contact info --}}
    <div class="booking-form__row booking-form__row--compact-mobile">
        <div class="booking-form__group">
            <label for="first_name">{{ __('app.booking_first_name') }} *</label>
            <input type="text" id="first_name" name="first_name" required minlength="3" maxlength="100" autocomplete="given-name">
            <span class="booking-form__field-error" data-error-for="first_name" hidden aria-live="polite"></span>
        </div>
        <div class="booking-form__group">
            <label for="last_name">{{ __('app.booking_last_name') }} *</label>
            <input type="text" id="last_name" name="last_name" required minlength="3" maxlength="100" autocomplete="family-name">
            <span class="booking-form__field-error" data-error-for="last_name" hidden aria-live="polite"></span>
        </div>
    </div>

    <div class="booking-form__row">
        <div class="booking-form__group">
            <label for="email">{{ __('app.booking_email') }} *</label>
            <input type="email" id="email" name="email" required maxlength="150" autocomplete="email">
            <span class="booking-form__field-error" data-error-for="email" hidden aria-live="polite"></span>
        </div>
        <div class="booking-form__group">
            <label for="phone">{{ __('app.booking_phone') }} *</label>
            <div class="phone-prefix-wrap" data-phone-prefix-wrap>
                <input type="hidden" name="phone_prefix" data-phone-prefix data-current-value="{{ old('phone_prefix') }}">
                <input type="tel" id="phone" name="phone" data-phone-number required maxlength="30" autocomplete="tel">
            </div>
            <span class="booking-form__field-error" data-error-for="phone" hidden aria-live="polite"></span>
        </div>
    </div>

    <div class="booking-form__group">
        <label for="message">{{ __('app.booking_message') }}</label>
        <textarea id="message" name="message" maxlength="1000"></textarea>
    </div>

    {{-- Newsletter opt-in --}}
    <div class="booking-form__newsletter">
        <span>{{ __('app.booking_newsletter') }}</span>
        <label class="toggle" aria-label="{{ __('app.booking_newsletter') }}">
            <input type="checkbox" name="newsletter" value="1">
            <span class="toggle__track"><span class="toggle__thumb"></span></span>
        </label>
    </div>

    {{-- Generic form-level error (network / server errors) --}}
    <span class="booking-form__field-error booking-form__field-error--center"
          data-error-for="_form" hidden aria-live="polite"></span>

    {{-- Mandatory legal consent: House Rules + Short-Term Tourist Lease Agreement --}}
    <div class="booking-form__consent">
        <label class="booking-form__consent-label">
            <input type="checkbox" name="accepted_terms" value="1" required>
            <span>{!! __('app.booking_terms_checkbox', ['rules_url' => route_locale('rules'), 'terms_url' => route_locale('terms')]) !!}</span>
        </label>
        <span class="booking-form__field-error" data-error-for="accepted_terms" hidden aria-live="polite"></span>
    </div>

    <button type="submit" class="btn btn--primary booking-form__submit" disabled>
        {{ __('app.booking_submit') }}
    </button>

    <p class="booking-form__note">{{ __('app.booking_note') }}</p>
</form>
