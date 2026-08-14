<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @if (app()->environment('production') && config('analytics.gtm.container_id'))
        @php($gtmContainerId = config('analytics.gtm.container_id'))
        <!-- Google Tag Manager -->
        <script>
            (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer','{{ $gtmContainerId }}');
        </script>
        <!-- End Google Tag Manager -->
    @endif

    {{-- SEO: title and description per page, with locale-specific fallback --}}
    <title>@yield('title', config('apartment.seo.' . app()->getLocale() . '.title', config('apartment.seo.it.title')))</title>
    <meta name="description" content="@yield('description', config('apartment.seo.' . app()->getLocale() . '.description', config('apartment.seo.it.description')))">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="{{ url()->current() }}">
    <meta property="og:title"       content="@yield('title', config('apartment.seo.' . app()->getLocale() . '.title'))">
    <meta property="og:description" content="@yield('description', config('apartment.seo.' . app()->getLocale() . '.description'))">
    <meta property="og:image"       content="@yield('og_image', asset(config('apartment.images.og')))">
    <meta property="og:locale"      content="{{ str_replace('-', '_', app()->getLocale()) }}">

    {{-- hreflang alternate links for multilingual SEO --}}
    @foreach (['it', 'en', 'fr', 'de'] as $loc)
        <link rel="alternate" hreflang="{{ $loc }}" href="{{ route_locale('home', [], $loc) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ route_locale('home') }}">

    {{-- Schema.org JSON-LD for local SEO --}}
    @stack('schema')

    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('images/brand/logo-symbol-blue.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('images/brand/logo-symbol-blue@3x.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/logo-symbol-blue@3x.png') }}">

    {{-- Google Fonts: Inter + Playfair Display (Montserrat self-hosted via @font-face) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">

    @if (app()->environment('production') && config('analytics.ga4.measurement_id'))
        @php($ga4MeasurementId = config('analytics.ga4.measurement_id'))
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4MeasurementId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', '{{ $ga4MeasurementId }}');
        </script>
    @endif

    {{-- Leaflet CSS (for map page) --}}
    @stack('head_css')

    {{-- Vite compiled assets --}}
    @vite(['resources/css/app.css', 'resources/ts/app.ts'])
</head>
<body>

    @if (app()->environment('production') && config('analytics.gtm.container_id'))
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmContainerId }}"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
    @endif

    {{-- Navigation --}}
    @include('components.nav')

    {{-- Main content --}}
    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    @include('components.footer')

    {{-- Leaflet JS or other page-specific scripts --}}
    @stack('scripts')

</body>
</html>
