<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO: title and description per page, with locale-specific fallback --}}
    <title>@yield('title', config('apartment.seo.' . app()->getLocale() . '.title', config('apartment.seo.it.title')))</title>
    <meta name="description" content="@yield('description', config('apartment.seo.' . app()->getLocale() . '.description', config('apartment.seo.it.description')))">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="{{ url()->current() }}">
    <meta property="og:title"       content="@yield('title', config('apartment.seo.' . app()->getLocale() . '.title'))">
    <meta property="og:description" content="@yield('description', config('apartment.seo.' . app()->getLocale() . '.description'))">
    <meta property="og:image"       content="@yield('og_image', asset('images/og-default.jpg'))">
    <meta property="og:locale"      content="{{ str_replace('-', '_', app()->getLocale()) }}">

    {{-- Schema.org JSON-LD for local SEO --}}
    @stack('schema')

    {{-- Google Fonts: Inter + Playfair Display --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">

    {{-- Leaflet CSS (for map page) --}}
    @stack('head_css')

    {{-- Vite compiled assets --}}
    @vite(['resources/css/app.css', 'resources/ts/app.ts'])
</head>
<body>

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
