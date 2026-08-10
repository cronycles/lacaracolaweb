@extends('layouts.app')

@section('title', __('app.checkin_page_title') . ' — ' . config('apartment.name'))

@push('scripts')
<script>window.COMUNI_VALIDI = @json($comuniNames);</script>
<script>window.COUNTRIES_MAP = @json($countries);</script>
@php
    $checkinI18n = [
        'birthMunicipalityLabel'        => __('app.checkin_js_birth_municipality'),
        'birthCityLabel'                => __('app.checkin_js_birth_city'),
        'municipalitySearchPlaceholder' => __('app.checkin_js_municipality_search'),
        'cityPlaceholderExample'        => __('app.checkin_js_city_example'),
    ];
@endphp
<script>window.CHECKIN_I18N = @json($checkinI18n);</script>
@vite(['resources/ts/checkin.ts'])
@endpush

@section('content')

<section class="section">
    <div class="container" style="max-width:720px">

        {{-- Language switcher: reloads with ?lang= override, does not persist --}}
        <div class="checkin-lang-switch">
            @foreach (['it', 'en', 'fr', 'de'] as $loc)
                <a href="{{ request()->fullUrlWithQuery(['lang' => $loc]) }}"
                   class="{{ app()->getLocale() === $loc ? 'is-active' : '' }}">{{ strtoupper($loc) }}</a>
            @endforeach
        </div>

        @php
            $checkinConfirmed = $booking->checkin_completed_at && $totalGuests >= $booking->total_guests;
        @endphp

        @if ($checkinConfirmed)
            <div class="checkin-callout checkin-callout--success" role="status">
                <strong class="checkin-callout__title">{{ __('app.checkin_already_confirmed_title') }}</strong>
                <span>{{ __('app.checkin_already_confirmed', ['date' => $booking->checkin_completed_at->translatedFormat('d/m/Y H:i')]) }}</span>

                <div class="checkin-summary">
                    <h2 class="checkin-summary__title">{{ __('app.checkin_summary_title') }}</h2>
                    @foreach ($guests as $guest)
                        <section class="checkin-summary__guest">
                            <h3>{{ $guest->full_name }}</h3>
                            <dl>
                                <div><dt>{{ __('app.checkin_field_gender') }}</dt><dd>{{ $guest->gender === 'M' ? __('app.checkin_gender_m') : __('app.checkin_gender_f') }}</dd></div>
                                <div><dt>{{ __('app.checkin_field_birth_date') }}</dt><dd>{{ $guest->birth_date?->translatedFormat('d/m/Y') ?? '—' }}</dd></div>
                                <div><dt>{{ __('app.checkin_field_nationality') }}</dt><dd>{{ $guest->nationality_code ?: '—' }}</dd></div>
                                <div><dt>{{ __('app.checkin_field_birth_country') }}</dt><dd>{{ $guest->birth_country_code ?: '—' }}</dd></div>
                                <div><dt>{{ __('app.checkin_field_birth_municipality') }}</dt><dd>{{ $guest->birth_municipality ?: '—' }}</dd></div>
                                @if ($guest->birth_province)
                                    <div><dt>{{ __('app.checkin_field_birth_province') }}</dt><dd>{{ $guest->birth_province }}</dd></div>
                                @endif
                                @if ($guest->document_type)
                                    <div><dt>{{ __('app.checkin_field_document_type') }}</dt><dd>{{ __('app.checkin_document_type_' . $guest->document_type) }}</dd></div>
                                    <div><dt>{{ __('app.checkin_field_document_number') }}</dt><dd>{{ $guest->document_number ?: '—' }}</dd></div>
                                    <div><dt>{{ __('app.checkin_field_document_issue_country') }}</dt><dd>{{ $guest->document_issue_country_code ?: '—' }}</dd></div>
                                    @if ($guest->document_issue_place)
                                        <div><dt>{{ __('app.checkin_field_document_issue_place') }}</dt><dd>{{ $guest->document_issue_place }}</dd></div>
                                    @endif
                                @endif
                            </dl>
                        </section>
                    @endforeach
                </div>

                <button type="button" class="btn btn--outline checkin-edit-button" data-checkin-edit-open>
                    {{ __('app.checkin_edit_button') }}
                </button>

                <dialog class="checkin-edit-dialog" data-checkin-edit-dialog aria-labelledby="checkin-edit-dialog-title">
                    <form method="POST" action="{{ route('checkin.edit', $booking->checkin_token) }}">
                        @csrf
                        <h2 id="checkin-edit-dialog-title">{{ __('app.checkin_edit_dialog_title') }}</h2>
                        <p>{{ __('app.checkin_edit_dialog_text') }}</p>
                        <div class="checkin-edit-dialog__actions">
                            <button type="button" class="btn btn--outline" data-checkin-edit-close>{{ __('app.checkin_edit_cancel') }}</button>
                            <button type="submit" class="btn btn--primary">{{ __('app.checkin_edit_confirm') }}</button>
                        </div>
                    </form>
                </dialog>
            </div>
        @endif

        <h1 class="section-title">{{ __('app.checkin_title') }}</h1>
        <p style="color:var(--color-text-muted);margin-bottom:var(--space-6)">
            {{ __('app.checkin_intro', [
                'checkin'  => $booking->checkin->translatedFormat('d F Y'),
                'checkout' => $booking->checkout->translatedFormat('d F Y'),
            ]) }}
        </p>

        @unless ($checkinConfirmed)
        <div class="checkin-progress checkin-progress--incomplete" data-checkin-progress
             data-required-guests="{{ $booking->total_guests }}"
             data-present-guests="{{ $totalGuests }}"
             data-progress-incomplete-label="{{ __('app.checkin_guest_progress') }}"
             data-progress-complete-label="{{ __('app.checkin_guest_progress_complete') }}"
             data-progress-template="{{ __('app.checkin_guest_progress_count', ['completed' => '__COMPLETED__', 'total' => $booking->total_guests]) }}"
             role="status" aria-live="polite">
            <strong data-checkin-progress-label>{{ __('app.checkin_guest_progress', ['total' => $booking->total_guests]) }}</strong>
            <span data-checkin-progress-count>{{ __('app.checkin_guest_progress_count', ['completed' => 0, 'total' => $booking->total_guests]) }}</span>
        </div>

        @if (session('success'))
            <div class="checkin-callout">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="checkin-callout checkin-callout--error">{{ session('error') }}</div>
        @endif
        <form id="checkin-form" method="POST" action="{{ route('checkin.confirm', $booking->checkin_token) }}">
            @csrf

            @foreach ($guests as $i => $guest)
                @php
                    $tipo = \App\Services\GuestReporting\GuestClassifier::defaultTipoFor($i, $totalGuests);
                    $requiresDoc = \App\Services\GuestReporting\GuestClassifier::requiresDocument($tipo);
                    $birthCountryCode = old("guests.{$i}.birth_country_code", $guest->birth_country_code);
                @endphp

                <div class="booking-form checkin-guest">
                    <div class="checkin-guest__title">
                        <strong>{{ __('app.checkin_guest_label', ['n' => $i + 1]) }}: {{ $guest->full_name }}</strong>
                        @if ($i === 0)
                            <span class="checkin-badge">{{ __('app.checkin_role_primary') }}</span>
                        @endif
                    </div>

                    <input type="hidden" name="guests[{{ $i }}][person_id]" value="{{ $guest->id }}">

                    <div class="booking-form__row">
                        <div class="booking-form__group">
                            <label for="guests_{{ $i }}_gender">{{ __('app.checkin_field_gender') }} *</label>
                            <select id="guests_{{ $i }}_gender" name="guests[{{ $i }}][gender]" required>
                                <option value="">—</option>
                                <option value="M" @selected(old("guests.{$i}.gender", $guest->gender) === 'M')>{{ __('app.checkin_gender_m') }}</option>
                                <option value="F" @selected(old("guests.{$i}.gender", $guest->gender) === 'F')>{{ __('app.checkin_gender_f') }}</option>
                            </select>
                            @error("guests.{$i}.gender") <span class="booking-form__field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="booking-form__group">
                            <label for="guests_{{ $i }}_birth_date">{{ __('app.checkin_field_birth_date') }} *</label>
                            <input type="date" id="guests_{{ $i }}_birth_date" name="guests[{{ $i }}][birth_date]"
                                   value="{{ old("guests.{$i}.birth_date", $guest->birth_date?->format('Y-m-d')) }}" required>
                            @error("guests.{$i}.birth_date") <span class="booking-form__field-error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="booking-form__row">
                        <div class="booking-form__group">
                            <label for="guests_{{ $i }}_nationality_code">{{ __('app.checkin_field_nationality') }} *</label>
                            <input type="text" id="guests_{{ $i }}_nationality_code"
                                   name="guests[{{ $i }}][nationality_code]" required
                                   data-country-combo
                                   data-current-value="{{ old("guests.{$i}.nationality_code", $guest->nationality_code) }}"
                                   autocomplete="off">
                            @error("guests.{$i}.nationality_code") <span class="booking-form__field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="booking-form__group">
                            <label for="guests_{{ $i }}_birth_country_code">{{ __('app.checkin_field_birth_country') }} *</label>
                            <input type="text" id="guests_{{ $i }}_birth_country_code"
                                   name="guests[{{ $i }}][birth_country_code]" required
                                   data-country-combo
                                   data-reporting-birth-country
                                   data-current-value="{{ old("guests.{$i}.birth_country_code", $guest->birth_country_code) }}"
                                   autocomplete="off">
                            @error("guests.{$i}.birth_country_code") <span class="booking-form__field-error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="booking-form__row">
                        <div class="booking-form__group" id="birth_province_group_{{ $i }}" data-birth-province-group
                             style="{{ $birthCountryCode !== 'IT' ? 'display:none' : '' }}">
                            <label for="guests_{{ $i }}_birth_province">{{ __('app.checkin_field_birth_province') }} *</label>
                            <input type="text" id="guests_{{ $i }}_birth_province"
                                   name="guests[{{ $i }}][birth_province]"
                                   value="{{ old("guests.{$i}.birth_province", $guest->birth_province) }}" maxlength="2" placeholder="Es: GE"
                                   data-normalize-uppercase
                                 @if($birthCountryCode === 'IT') required @endif>
                            @error("guests.{$i}.birth_province") <span class="booking-form__field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="booking-form__group">
                            <label for="guests_{{ $i }}_birth_municipality">{{ __('app.checkin_field_birth_municipality') }} *</label>
                            <input type="text" id="guests_{{ $i }}_birth_municipality"
                                   name="guests[{{ $i }}][birth_municipality]" maxlength="100" required
                                   data-reporting-birth-municipality
                                   data-normalize-capitalized
                                   value="{{ old("guests.{$i}.birth_municipality", $guest->birth_municipality) }}"
                                   data-current-value="{{ old("guests.{$i}.birth_municipality", $guest->birth_municipality) }}">
                            @error("guests.{$i}.birth_municipality") <span class="booking-form__field-error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div data-document-fields-group @unless($requiresDoc) style="display:none" @endunless>
                        <div class="booking-form__row">
                            <div class="booking-form__group">
                                <label for="guests_{{ $i }}_document_type">{{ __('app.checkin_field_document_type') }} *</label>
                                <select id="guests_{{ $i }}_document_type" name="guests[{{ $i }}][document_type]" @if($requiresDoc) required @endif>
                                    <option value="">—</option>
                                    <option value="passport" @selected(old("guests.{$i}.document_type", $guest->document_type) === 'passport')>{{ __('app.checkin_document_type_passport') }}</option>
                                    <option value="id_card" @selected(old("guests.{$i}.document_type", $guest->document_type) === 'id_card')>{{ __('app.checkin_document_type_id_card') }}</option>
                                    <option value="driving_license" @selected(old("guests.{$i}.document_type", $guest->document_type) === 'driving_license')>{{ __('app.checkin_document_type_driving_license') }}</option>
                                    <option value="residence_permit" @selected(old("guests.{$i}.document_type", $guest->document_type) === 'residence_permit')>{{ __('app.checkin_document_type_residence_permit') }}</option>
                                    <option value="other" @selected(old("guests.{$i}.document_type", $guest->document_type) === 'other')>{{ __('app.checkin_document_type_other') }}</option>
                                </select>
                                @error("guests.{$i}.document_type") <span class="booking-form__field-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="booking-form__group">
                                <label for="guests_{{ $i }}_document_number">{{ __('app.checkin_field_document_number') }} *</label>
                                <input type="text" id="guests_{{ $i }}_document_number"
                                       name="guests[{{ $i }}][document_number]" maxlength="60"
                                       value="{{ old("guests.{$i}.document_number", $guest->document_number) }}"
                                       @if($requiresDoc) required @endif>
                                @error("guests.{$i}.document_number") <span class="booking-form__field-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="booking-form__row">
                            <div class="booking-form__group">
                                <label for="guests_{{ $i }}_document_issue_country_code">{{ __('app.checkin_field_document_issue_country') }} *</label>
                                <input type="text" id="guests_{{ $i }}_document_issue_country_code"
                                       name="guests[{{ $i }}][document_issue_country_code]"
                                       data-country-combo
                                       data-reporting-issue-country
                                       data-current-value="{{ old("guests.{$i}.document_issue_country_code", $guest->document_issue_country_code) }}"
                                       autocomplete="off"
                                       @if($requiresDoc) required @endif>
                                @error("guests.{$i}.document_issue_country_code") <span class="booking-form__field-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="booking-form__group" data-document-issue-place-group
                                 style="{{ old("guests.{$i}.document_issue_country_code", $guest->document_issue_country_code) !== 'IT' ? 'display:none' : '' }}">
                                <label for="guests_{{ $i }}_document_issue_place">{{ __('app.checkin_field_document_issue_place') }}</label>
                                <input type="text" id="guests_{{ $i }}_document_issue_place"
                                       name="guests[{{ $i }}][document_issue_place]" maxlength="100"
                                       data-reporting-issue-municipality
                                       value="{{ old("guests.{$i}.document_issue_place", $guest->document_issue_place) }}">
                                @error("guests.{$i}.document_issue_place") <span class="booking-form__field-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

        </form>

        @if ($canAddCompanion)
            <form method="POST" action="{{ route('checkin.companions.store', $booking->checkin_token) }}" class="booking-form" style="margin-top:var(--space-8)" data-companion-form>
                @csrf
                <div class="booking-form__title">{{ __('app.checkin_add_companion_title') }}</div>
                <div class="booking-form__row">
                    <div class="booking-form__group">
                        <label for="companion_first_name">{{ __('app.checkin_field_first_name') }} *</label>
                        <input type="text" id="companion_first_name" name="first_name" required maxlength="100" data-normalize-capitalized>
                    </div>
                    <div class="booking-form__group">
                        <label for="companion_last_name">{{ __('app.checkin_field_last_name') }} *</label>
                        <input type="text" id="companion_last_name" name="last_name" required maxlength="100" data-normalize-capitalized>
                    </div>
                </div>
                <button type="submit" class="btn btn--outline" style="margin-top:var(--space-4)">{{ __('app.checkin_add_companion_button') }}</button>
            </form>
        @endif

        <div class="checkin-submit">
            <div class="checkin-callout checkin-callout--error" data-checkin-submit-error hidden role="alert">
                {{ __('app.checkin_incomplete_error') }}
            </div>
            <button type="submit" form="checkin-form" class="btn btn--primary" data-checkin-submit>
                {{ __('app.checkin_confirm_button') }}
            </button>
            <p class="booking-form__note">{{ __('app.checkin_confirm_note') }}</p>
        </div>
        @endunless

    </div>
</section>

@endsection
