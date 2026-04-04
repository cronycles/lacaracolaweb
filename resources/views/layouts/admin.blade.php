<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — La Caracola</title>
    @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/css/components/calendar.css', 'resources/ts/app.ts'])
</head>
<body>
    {{-- Sidebar navigation --}}
    <aside class="admin-sidebar">
        <a href="{{ route('admin.dashboard') }}" class="admin-sidebar__brand">
            <img src="{{ asset('images/brand/logo-symbol-gold.svg') }}" alt="La Caracola" class="admin-sidebar__brand__logo">
            <div>
                La Caracola
                <span>Pannello di controllo</span>
            </div>
        </a>

        <ul class="admin-nav">
            <li>
                <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])>
                    📊 Dashboard
                </a>
            </li>
            <li>
                <a href="{{ route('admin.calendar') }}" @class(['active' => request()->routeIs('admin.calendar*')])>
                    📅 Calendario
                </a>
            </li>
            <li>
                <a href="{{ route('admin.pricing.index') }}" @class(['active' => request()->routeIs('admin.pricing*')])>
                    💶 Prezzi
                </a>
            </li>
            <li>
                <a href="{{ route('admin.stay-discounts.index') }}" @class(['active' => request()->routeIs('admin.stay-discounts*')])>
                    🏷️ Sconti soggiorno
                </a>
            </li>
            <li>
                <a href="{{ route('admin.bookings.index') }}" @class(['active' => request()->routeIs('admin.bookings*')])>
                    🏠 Prenotazioni
                </a>
            </li>
            <li>
                <a href="{{ route('admin.bookings.import-pdf') }}" @class(['active' => request()->routeIs('admin.bookings.import-pdf*')])>
                    📄 Import PDF
                </a>
            </li>
            <li>
                <a href="{{ route('admin.people.index') }}" @class(['active' => request()->routeIs('admin.people*')])>
                    👥 Ospiti
                </a>
            </li>
            <li>
                <a href="{{ route('admin.newsletter') }}" @class(['active' => request()->routeIs('admin.newsletter*')])>
                    ✉️ Newsletter
                </a>
            </li>
            <li>
                <a href="{{ route('admin.settings') }}" @class(['active' => request()->routeIs('admin.settings*')])>
                    ⚙️ Impostazioni
                </a>
            </li>
            <li>
                <a href="{{ route('admin.account-security') }}" @class(['active' => request()->routeIs('admin.account-security*')])>
                    🔐 Sicurezza Account
                </a>
            </li>
        </ul>

        <div class="admin-sidebar__footer">
            <a href="{{ url('/') }}" target="_blank">← Sito pubblico</a>
            <br style="margin-bottom:.4rem">
            <form method="POST" action="{{ route('logout') }}" style="display:inline">
                @csrf
                <button type="submit" style="background:none;border:none;cursor:pointer;color:inherit;font-size:.8rem;padding:0">
                    Esci
                </button>
            </form>
        </div>
    </aside>

    {{-- Main area --}}
    <div class="admin-main">
        <header class="admin-topbar">
            <span class="admin-topbar__title">@yield('title', 'Dashboard')</span>
            <span style="color:#6b7f89;font-size:.8rem">
                {{ auth()->user()->email ?? '' }}
            </span>
        </header>

        <main class="admin-content">
            @if (session('success'))
                <div class="flash flash--success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="flash flash--error">{{ session('error') }}</div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
