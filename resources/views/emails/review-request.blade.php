@extends('emails.layout')

@section('title', __('app.review_request_mail_subject'))

@section('content')
    <h1>🐚 {{ __('app.review_request_mail_title') }}</h1>
    <p>{!! str_replace('La Caracola', '<strong>La Caracola</strong>', __('app.review_request_mail_thanks')) !!}</p>
    <p>{{ __('app.review_request_mail_intro', ['checkout' => $booking->checkout->translatedFormat('d F Y')]) }}</p>
    <p><a href="{{ $reviewUrl }}" class="btn">{{ __('app.review_request_mail_button') }}</a></p>
    <div class="footer">{{ __('app.review_request_mail_footer') }}</div>
@endsection
