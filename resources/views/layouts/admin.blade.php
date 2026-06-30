<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — La Caracola</title>
    @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/css/components/calendar.css', 'resources/ts/app.ts', 'resources/ts/admin.ts'])
</head>
<body>
    {{-- Sidebar navigation --}}
    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="admin-sidebar__top">
            <a href="{{ route('admin.dashboard') }}" class="admin-sidebar__brand">
                <img src="{{ asset('images/brand/logo-symbol-gold.svg') }}" alt="La Caracola" class="admin-sidebar__brand__logo">
                <div>
                    La Caracola
                    <span>Pannello di controllo</span>
                </div>
            </a>
            <button class="admin-nav-toggle" type="button" aria-label="Apri menu" aria-expanded="false" id="admin-nav-toggle">
                <span class="admin-nav-toggle__icon">☰</span>
            </button>
        </div>

        <div class="admin-nav-collapse" id="admin-nav-collapse">
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
            @if(auth()->user()->hasPermission('manage_pricing'))
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
            @endif
            <li>
                <a href="{{ route('admin.bookings.index') }}" @class(['active' => request()->routeIs('admin.bookings*')])>
                    🏠 Prenotazioni
                </a>
            </li>
            @if(auth()->user()->hasPermission('manage_bookings'))
            <li>
                <a href="{{ route('admin.guest-reporting.index') }}" @class(['active' => request()->routeIs('admin.guest-reporting*')])>
                    📋 Segnalazione Ospiti
                </a>
            </li>
            @endif
            @if(auth()->user()->hasPermission('import_pdf'))
            <li>
                <a href="{{ route('admin.bookings.import-pdf') }}" @class(['active' => request()->routeIs('admin.bookings.import-pdf*')])>
                    📄 Import PDF
                </a>
            </li>
            @endif
            <li>
                <a href="{{ route('admin.people.index') }}" @class(['active' => request()->routeIs('admin.people*')])>
                    👥 Ospiti
                </a>
            </li>
            @if(auth()->user()->hasPermission('view_accounting'))
            <li>
                <a href="{{ route('admin.finance.index') }}" @class(['active' => request()->routeIs('admin.finance*')])>
                    📒 Contabilità
                </a>
            </li>
            @endif
            @if(auth()->user()->hasPermission('manage_newsletter'))
            <li>
                <a href="{{ route('admin.newsletter') }}" @class(['active' => request()->routeIs('admin.newsletter*')])>
                    ✉️ Newsletter
                </a>
            </li>
            @endif
            @if(auth()->user()->hasPermission('manage_reviews'))
            <li>
                <a href="{{ route('admin.reviews.index') }}" @class(['active' => request()->routeIs('admin.reviews*')])>
                    ⭐ Recensioni
                </a>
            </li>
            @endif
            @if(auth()->user()->hasPermission('manage_settings'))
            <li>
                <a href="{{ route('admin.settings') }}" @class(['active' => request()->routeIs('admin.settings*')])>
                    ⚙️ Impostazioni
                </a>
            </li>
            @endif
            <li>
                <a href="{{ route('admin.account-security') }}" @class(['active' => request()->routeIs('admin.account-security*')])>
                    🔐 Sicurezza Account
                </a>
            </li>
            @if(auth()->user()->isSuperAdmin())
            <li>
                <a href="{{ route('admin.users.index') }}" @class(['active' => request()->routeIs('admin.users*')])>
                    👤 Utenti
                </a>
            </li>
            @endif
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
        </div>{{-- /admin-nav-collapse --}}
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
    @stack('dialogs')
    @stack('scripts')
    <script>
    (function(){
        var toggle = document.getElementById('admin-nav-toggle');
        var sidebar = document.getElementById('admin-sidebar');
        if (!toggle || !sidebar) return;
        toggle.addEventListener('click', function(){
            var open = sidebar.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        // close sidebar when a nav link is tapped on mobile
        sidebar.querySelectorAll('.admin-nav a').forEach(function(a){
            a.addEventListener('click', function(){
                sidebar.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            });
        });
    })();
    </script>
</body>
</html>
