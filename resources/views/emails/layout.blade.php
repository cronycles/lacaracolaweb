<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', config('apartment.name'))</title>
    <style>
        body { font-family: Arial, sans-serif; color: #1a1a1a; font-size: 15px; line-height: 1.6; margin: 0; padding: 0; background: #f8f9fa; }
        .email-wrapper { max-width: 600px; margin: 0 auto; padding: 24px; }
        .email-header { text-align: center; padding-bottom: 16px; border-bottom: 3px solid #c7b772; margin-bottom: 24px; }
        .email-header img { max-width: 180px; height: auto; }
        h1 { font-size: 20px; color: #30596C; border-bottom: 2px solid #30596C; padding-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        th { text-align: left; font-weight: bold; width: 38%; color: #5a6a70; padding: 6px 0; vertical-align: top; }
        td { padding: 6px 0; }
        .section { margin-top: 20px; }
        .section-title { font-weight: bold; color: #30596C; margin-bottom: 4px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; }
        .payment-section { background: #eef5f7; border: 2px solid #30596C; border-radius: 6px; padding: 20px; margin-top: 24px; }
        .payment-section .payment-title { color: #30596C; font-size: 18px; font-weight: bold; margin-bottom: 10px; text-transform: uppercase; }
        .payment-section table { margin: 12px 0 0; }
        .payment-deadline { background: #ffffff; color: #5d666b; padding: 12px 16px; border-radius: 4px; margin-top: 18px; }
        .payment-deadline strong { color: #1a1a1a; }
        .message-box { background: #f5f5f5; border-left: 4px solid #30596C; padding: 12px 16px; border-radius: 4px; margin-top: 8px; white-space: pre-wrap; }
        .callout { background: #fbf8ef; padding: 12px 16px; border-radius: 4px; margin-top: 16px; }
        .footer { margin-top: 32px; font-size: 12px; color: #888; border-top: 1px solid #dde3e6; padding-top: 16px; }
        .badge { display: inline-block; background: #30596C; color: #fff; border-radius: 4px; padding: 2px 10px; font-size: 13px; }
        .btn { display: inline-block; background: #30596C; color: #ffffff !important; text-decoration: none; padding: 10px 22px; border-radius: 4px; margin-top: 12px; font-weight: bold; }
    </style>
</head>
<body>
<div class="email-wrapper">
    <div class="email-header">
        <img src="{{ asset('images/brand/logo-wordmark-blue@3x.png') }}" alt="{{ config('apartment.name') }}">
    </div>

    @yield('content')

</div>
</body>
</html>
