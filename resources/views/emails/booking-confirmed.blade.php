@extends('emails.layout')

@section('title', __('app.booking_confirmed_mail_subject'))

@section('content')

    <h1>🐚 {{ __('app.booking_confirmed_mail_title') }}</h1>

    <p>{{ __('app.booking_confirmed_mail_intro', [
        'checkin'  => $booking->checkin->translatedFormat('d F Y'),
        'checkout' => $booking->checkout->translatedFormat('d F Y'),
    ]) }}</p>

    <div class="section">
        <div class="section-title">{{ __('app.booking_confirmed_mail_summary_title') }}</div>
        <table>
            <tr>
                <th>{{ __('app.booking_adults') }}</th>
                <td>{{ $booking->adults }}</td>
            </tr>
            @if ($booking->children > 0)
            <tr>
                <th>{{ __('app.booking_children') }}</th>
                <td>{{ $booking->children }}</td>
            </tr>
            @endif
            @if ($booking->babies > 0)
            <tr>
                <th>{{ __('app.booking_babies') }}</th>
                <td>{{ $booking->babies }}</td>
            </tr>
            @endif
            @if ($booking->pets > 0)
            <tr>
                <th>{{ __('app.booking_pets') }}</th>
                <td>{{ $booking->pets }}</td>
            </tr>
            @endif
            @if ($booking->total_price !== null)
            <tr>
                <th>{{ __('app.booking_confirmed_mail_summary_total') }}</th>
                <td>€ {{ number_format($booking->total_price, 2, ',', '.') }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">{{ __('app.booking_confirmed_mail_payment_title') }}</div>
        <p>{{ __('app.booking_confirmed_mail_payment_intro', ['hours' => config('apartment.payment.deadline_hours', 48)]) }}</p>
        <table>
            @if (config('apartment.payment.beneficiary'))
            <tr>
                <th>{{ __('app.booking_confirmed_mail_beneficiary') }}</th>
                <td>{{ config('apartment.payment.beneficiary') }}</td>
            </tr>
            @endif
            @if (config('apartment.payment.iban'))
            <tr>
                <th>{{ __('app.booking_confirmed_mail_iban') }}</th>
                <td>{{ config('apartment.payment.iban') }}</td>
            </tr>
            @endif
            @if (config('apartment.payment.bic'))
            <tr>
                <th>{{ __('app.booking_confirmed_mail_bic') }}</th>
                <td>{{ config('apartment.payment.bic') }}</td>
            </tr>
            @endif
            <tr>
                <th>{{ __('app.booking_confirmed_mail_reference') }}</th>
                <td>{{ __('app.booking_confirmed_mail_reference_value', [
                    'checkin'  => $booking->checkin->translatedFormat('d/m/Y'),
                    'checkout' => $booking->checkout->translatedFormat('d/m/Y'),
                ]) }}</td>
            </tr>
        </table>

        <div class="callout">
            {{ __('app.booking_confirmed_mail_deadline', ['deadline' => $paymentDeadline->translatedFormat('d F Y \a\l\l\e H:i')]) }}
        </div>
    </div>

    @if ($cancellationStillFree)
    <div class="section">
        <div class="section-title">{{ __('app.booking_confirmed_mail_cancellation_title') }}</div>
        <p>{{ __('app.booking_confirmed_mail_cancellation', ['date' => $freeCancellationDate->translatedFormat('d F Y')]) }}</p>
    </div>
    @endif

    @if ($booking->bookingRequest)
    <div class="callout">
        {!! __('app.booking_confirmed_mail_terms', [
            'date'      => $booking->bookingRequest->terms_accepted_at->translatedFormat('d F Y \a\l\l\e H:i'),
            'rules_url' => route_locale('rules'),
            'terms_url' => route_locale('terms'),
        ]) !!}
    </div>
    @endif

    <div class="section">
        <div class="section-title">{{ __('app.booking_confirmed_mail_checkin_title') }}</div>
        <p>{{ __('app.booking_confirmed_mail_checkin_text') }}</p>
        <p><a href="{{ $checkinUrl }}" class="btn">{{ __('app.booking_confirmed_mail_checkin_button') }}</a></p>
    </div>

    <div class="footer">
        {{ __('app.booking_confirmed_mail_footer') }}
    </div>

@endsection
