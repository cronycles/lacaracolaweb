@extends('emails.layout')

@section('title', __('app.checkin_reminder_mail_subject'))

@section('content')

    <h1>🐚 {{ __('app.checkin_reminder_mail_title') }}</h1>

    <p>{{ __('app.checkin_reminder_mail_intro', [
        'checkin' => $booking->checkin->translatedFormat('d F Y'),
    ]) }}</p>

    <p><a href="{{ $checkinUrl }}" class="btn">{{ __('app.checkin_reminder_mail_button') }}</a></p>

    <div class="footer">
        {{ __('app.checkin_reminder_mail_footer') }}
    </div>

@endsection
