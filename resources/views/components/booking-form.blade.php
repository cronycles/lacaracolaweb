{{-- Booking availability request form component --}}
{{-- Shared between home page (embedded) and standalone booking page --}}

@if(session('errors') && session('errors')->has('checkout'))
    <div class="booking-form__error" style="display:block;color:#dc2626;margin-bottom:1rem;padding:.75rem;background:#fef2f2;border-radius:.5rem">
        {{ session('errors')->first('checkout') }}
    </div>
@endif

<form id="booking-form"
      method="POST"
      action="{{ route('booking.request') }}"
      class="booking-form"
      data-error-past="{{ __('app.error_min_nights', ['nights' => config('apartment.booking.min_nights', 3)]) }}"
      data-error-order="Check-out deve essere dopo il check-in."
      data-error-min-nights="{{ __('app.error_min_nights', ['nights' => config('apartment.booking.min_nights', 3)]) }}"
      novalidate>
    @csrf

    <h3 class="booking-form__title">{{ __('app.booking_title') }}</h3>

    {{-- Dates row --}}
    <div class="booking-form__row">
        <div class="booking-form__group">
            <label for="checkin">{{ __('app.booking_checkin') }} *</label>
            <input type="date" id="checkin" name="checkin"
                   value="{{ old('checkin') }}"
                   min="{{ date('Y-m-d') }}"
                   required>
        </div>
        <div class="booking-form__group">
            <label for="checkout">{{ __('app.booking_checkout') }} *</label>
            <input type="date" id="checkout" name="checkout"
                   value="{{ old('checkout') }}"
                   min="{{ date('Y-m-d', strtotime('+3 days')) }}"
                   required>
        </div>
    </div>

    {{-- Guests row --}}
    <div class="booking-form__row">
        <div class="booking-form__group">
            <label for="adults">{{ __('app.booking_adults') }} *</label>
            <select id="adults" name="adults" required>
                @foreach (range(1, 6) as $i)
                    <option value="{{ $i }}" {{ old('adults', 2) == $i ? 'selected' : '' }}>{{ $i }}</option>
                @endforeach
            </select>
        </div>
        <div class="booking-form__group">
            <label for="children">{{ __('app.booking_children') }}</label>
            <select id="children" name="children">
                @foreach (range(0, 5) as $i)
                    <option value="{{ $i }}" {{ old('children', 0) == $i ? 'selected' : '' }}>{{ $i }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Contact info --}}
    <div class="booking-form__group" style="margin-top:.5rem">
        <label for="name">{{ __('app.booking_name') }} *</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name">
    </div>

    <div class="booking-form__row" style="margin-top:.5rem">
        <div class="booking-form__group">
            <label for="email">{{ __('app.booking_email') }} *</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="150" autocomplete="email">
        </div>
        <div class="booking-form__group">
            <label for="phone">{{ __('app.booking_phone') }}</label>
            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" maxlength="30" autocomplete="tel">
        </div>
    </div>

    <div class="booking-form__group" style="margin-top:.5rem">
        <label for="message">{{ __('app.booking_message') }}</label>
        <textarea id="message" name="message" maxlength="1000">{{ old('message') }}</textarea>
    </div>

    {{-- Newsletter opt-in --}}
    <label style="display:flex;align-items:center;gap:.5rem;margin-top:1rem;font-size:.9rem;cursor:pointer">
        <input type="checkbox" name="newsletter" value="1" {{ old('newsletter') ? 'checked' : '' }}>
        {{ __('app.booking_newsletter') }}
    </label>

    {{-- Error placeholder (populated by TS) --}}
    <div class="booking-form__error" style="display:none;color:#dc2626;margin-top:.75rem;font-size:.85rem"></div>

    <button type="submit" class="btn btn--primary booking-form__submit">
        {{ __('app.booking_submit') }}
    </button>

    <p class="booking-form__note">{{ __('app.booking_note') }}</p>
</form>
