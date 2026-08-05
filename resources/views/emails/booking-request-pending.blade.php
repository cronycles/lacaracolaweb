@extends('emails.layout')

@section('title', __('app.booking_pending_mail_subject'))

@section('content')

    <h1>🐚 {{ __('app.booking_pending_mail_title') }}</h1>

    <p>{{ __('app.booking_pending_mail_intro', [
        'checkin'  => \Carbon\Carbon::parse($bookingRequest->checkin)->translatedFormat('d F Y'),
        'checkout' => \Carbon\Carbon::parse($bookingRequest->checkout)->translatedFormat('d F Y'),
    ]) }}</p>

    <div class="callout">
        {{ __('app.booking_pending_mail_terms', [
            'date' => $bookingRequest->terms_accepted_at->translatedFormat('d F Y \a\l\l\e H:i'),
        ]) }}
    </div>

    <div class="footer">
        {{ __('app.booking_pending_mail_footer') }}
    </div>

@endsection
