@extends('emails.layout')

@section('title', __('app.booking_declined_mail_subject'))

@section('content')

    <h1>🐚 {{ __('app.booking_declined_mail_title') }}</h1>

    <p>{{ __('app.booking_declined_mail_intro', [
        'checkin'  => \Carbon\Carbon::parse($bookingRequest->checkin)->translatedFormat('d F Y'),
        'checkout' => \Carbon\Carbon::parse($bookingRequest->checkout)->translatedFormat('d F Y'),
    ]) }}</p>

    <p>{{ __('app.booking_declined_mail_apology') }}</p>

    <div class="footer">
        {{ __('app.booking_declined_mail_footer') }}
    </div>

@endsection
