@extends('layouts.app')

@section('title', __('app.contact_title') . ' — ' . config('apartment.name'))
@section('description', __('app.contact_meta_description'))

@section('content')

<section class="section">
    <div class="container">
        <h1 class="section-title" style="text-align:center">{{ __('app.contact_title') }}</h1>
        <p class="section-subtitle" style="text-align:center;margin-inline:auto">{{ __('app.contact_subtitle') }}</p>

        <div class="contact-form-wrap">

            @if(session('contact_sent'))
            {{-- Success state --}}
            <div class="booking-form booking-form--success" role="alert">
                <div class="booking-form__success-icon" aria-hidden="true">✓</div>
                <h3>{{ __('app.contact_thanks_title') }}</h3>
                <p>{{ __('app.contact_thanks_text') }}</p>
            </div>
            @else
            {{-- Contact form --}}
            <form method="POST"
                  action="{{ route_locale('contact.send') }}"
                  class="booking-form"
                  novalidate>
                @csrf

                {{-- Honeypot: must remain empty — filled only by bots --}}
                <input type="text" name="website" tabindex="-1" autocomplete="off"
                       style="position:absolute;opacity:0;height:0;width:0;pointer-events:none" aria-hidden="true">

                <div class="booking-form__group">
                    <label for="name">{{ __('app.contact_name') }} *</label>
                    <input type="text"
                           id="name"
                           name="name"
                           required
                           maxlength="100"
                           autocomplete="name"
                           value="{{ old('name') }}"
                           class="{{ $errors->has('name') ? 'is-invalid' : '' }}">
                    @error('name')
                        <span class="booking-form__field-error" style="display:block" aria-live="polite">{{ $message }}</span>
                    @enderror
                </div>

                <div class="booking-form__group">
                    <label for="email">{{ __('app.contact_email') }} *</label>
                    <input type="email"
                           id="email"
                           name="email"
                           required
                           maxlength="150"
                           autocomplete="email"
                           value="{{ old('email') }}"
                           class="{{ $errors->has('email') ? 'is-invalid' : '' }}">
                    @error('email')
                        <span class="booking-form__field-error" style="display:block" aria-live="polite">{{ $message }}</span>
                    @enderror
                </div>

                <div class="booking-form__group">
                    <label for="subject">{{ __('app.contact_subject') }}</label>
                    <input type="text"
                           id="subject"
                           name="subject"
                           maxlength="150"
                           value="{{ old('subject') }}"
                           class="{{ $errors->has('subject') ? 'is-invalid' : '' }}">
                    @error('subject')
                        <span class="booking-form__field-error" style="display:block" aria-live="polite">{{ $message }}</span>
                    @enderror
                </div>

                <div class="booking-form__group">
                    <label for="message">{{ __('app.contact_message') }} *</label>
                    <textarea id="message"
                              name="message"
                              required
                              minlength="10"
                              maxlength="2000"
                              rows="6"
                              class="{{ $errors->has('message') ? 'is-invalid' : '' }}">{{ old('message') }}</textarea>
                    @error('message')
                        <span class="booking-form__field-error" style="display:block" aria-live="polite">{{ $message }}</span>
                    @enderror
                </div>

                @error('_form')
                    <span class="booking-form__field-error booking-form__field-error--center"
                          style="display:block" aria-live="polite">{{ $message }}</span>
                @enderror

                <button type="submit" class="btn btn--primary booking-form__submit">
                    {{ __('app.contact_submit') }}
                </button>

                <p class="booking-form__note">{{ __('app.contact_note') }}</p>
            </form>
            @endif

        </div>
    </div>
</section>

@endsection
