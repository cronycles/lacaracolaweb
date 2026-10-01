@extends('emails.layout')

@section('title', $campaign->subject)

@section('content')
    @include('emails.partials.newsletter-blocks', ['blocks' => $campaign->content_it])

    <div style="border-top:1px solid #dde3e6;margin:28px 0 22px;padding-top:12px;text-align:center;color:#6b7f89;font-size:12px;text-transform:uppercase;letter-spacing:.08em">
        English version
    </div>

    @include('emails.partials.newsletter-blocks', ['blocks' => $campaign->content_en])

    <div class="callout" style="text-align:center;margin-top:28px">
        <strong>Visita il sito e prenota il tuo soggiorno</strong><br>
        Scopri La Caracola e verifica le disponibilità.
        <br><a class="btn" href="{{ route('it.home') }}">Visita il sito</a>
        <br><br>
        <strong>Visit our website and book your stay</strong><br>
        Discover La Caracola and check availability.
        <br><a class="btn" href="{{ route('en.home') }}">Visit the website</a>
    </div>

    <div class="footer" style="text-align:center">
        Hai ricevuto questa email perché ti sei iscritto alla newsletter.<br>
        You received this email because you subscribed to the newsletter.<br>
        <a href="{{ URL::signedRoute('newsletter.unsubscribe', ['email' => $recipient]) }}" style="color:#30596C">Disiscriviti / Unsubscribe</a>
    </div>
@endsection