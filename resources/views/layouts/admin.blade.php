<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — La Caracola</title>
    @vite(['resources/css/app.css'])
    <style>
        /* Admin-only layout variables */
        :root {
            --admin-sidebar-w: 220px;
            --admin-bg: #f4f6f8;
            --admin-sidebar-bg: #1e3d4a;
            --admin-sidebar-text: #c8d8e0;
            --admin-sidebar-active: #c7b772;
            --admin-border: #dde3e8;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--admin-bg);
            color: #1a2b34;
            min-height: 100vh;
            display: flex;
        }

        /* Sidebar */
        .admin-sidebar {
            width: var(--admin-sidebar-w);
            min-height: 100vh;
            background: var(--admin-sidebar-bg);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .admin-sidebar__brand {
            padding: 1.5rem 1rem;
            font-size: 1rem;
            font-weight: 700;
            color: var(--admin-sidebar-active);
            border-bottom: 1px solid rgba(255,255,255,.1);
            text-decoration: none;
            display: block;
        }

        .admin-sidebar__brand span {
            display: block;
            font-size: .7rem;
            font-weight: 400;
            color: var(--admin-sidebar-text);
            margin-top: .2rem;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .admin-nav {
            list-style: none;
            padding: 1rem 0;
            flex: 1;
        }

        .admin-nav li a {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .65rem 1rem;
            color: var(--admin-sidebar-text);
            text-decoration: none;
            font-size: .875rem;
            transition: background .15s, color .15s;
            border-left: 3px solid transparent;
        }

        .admin-nav li a:hover,
        .admin-nav li a.active {
            background: rgba(255,255,255,.08);
            color: #fff;
            border-left-color: var(--admin-sidebar-active);
        }

        .admin-sidebar__footer {
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,.1);
            font-size: .8rem;
            color: var(--admin-sidebar-text);
        }

        .admin-sidebar__footer a {
            color: var(--admin-sidebar-text);
            text-decoration: none;
        }

        .admin-sidebar__footer a:hover { color: #fff; }

        /* Main content */
        .admin-main {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .admin-topbar {
            background: #fff;
            padding: .75rem 1.5rem;
            border-bottom: 1px solid var(--admin-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: .875rem;
        }

        .admin-topbar__title {
            font-weight: 600;
            font-size: 1rem;
        }

        .admin-content {
            padding: 1.5rem;
            flex: 1;
        }

        /* Flash messages */
        .flash {
            padding: .75rem 1rem;
            border-radius: .375rem;
            margin-bottom: 1rem;
            font-size: .875rem;
        }
        .flash--success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .flash--error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        /* Card */
        .a-card {
            background: #fff;
            border: 1px solid var(--admin-border);
            border-radius: .5rem;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .a-card__title {
            font-size: .875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #6b7f89;
            margin-bottom: 1rem;
        }

        /* Table */
        .a-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .875rem;
        }

        .a-table th,
        .a-table td {
            padding: .6rem .75rem;
            text-align: left;
            border-bottom: 1px solid var(--admin-border);
        }

        .a-table th {
            font-size: .75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #6b7f89;
            background: #f9fafb;
        }

        .a-table tr:hover td { background: #f6f8fa; }

        /* Badges */
        .badge {
            font-size: .7rem;
            font-weight: 600;
            padding: .2rem .5rem;
            border-radius: 9999px;
            text-transform: capitalize;
            white-space: nowrap;
        }
        .badge--direct    { background: #dbeafe; color: #1e40af; }
        .badge--airbnb    { background: #fee2e2; color: #991b1b; }
        .badge--booking   { background: #e0f2fe; color: #075985; }
        .badge--interhome { background: #fef9c3; color: #854d0e; }
        .badge--owner     { background: #f3e8ff; color: #6b21a8; }
        .badge--maintenance { background: #fef3c7; color: #92400e; }
        .badge--booked    { background: #dcfce7; color: #166534; }

        /* Buttons */
        .btn { display: inline-flex; align-items: center; gap: .4rem; padding: .45rem .9rem; border-radius: .375rem; font-size: .875rem; font-weight: 500; cursor: pointer; border: none; text-decoration: none; transition: background .15s, color .15s; }
        .btn--primary   { background: #30596C; color: #fff; }
        .btn--primary:hover { background: #245069; }
        .btn--accent    { background: #c7b772; color: #1a2b34; }
        .btn--accent:hover { background: #b5a55e; }
        .btn--danger    { background: #dc2626; color: #fff; }
        .btn--danger:hover { background: #b91c1c; }
        .btn--outline   { background: transparent; border: 1px solid var(--admin-border); color: #374151; }
        .btn--outline:hover { background: #f1f5f9; }
        .btn--sm { font-size: .75rem; padding: .3rem .6rem; }

        /* Forms */
        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; font-size: .8rem; font-weight: 600; color: #374151; margin-bottom: .35rem; }
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: .5rem .75rem;
            border: 1px solid var(--admin-border);
            border-radius: .375rem;
            font-size: .875rem;
            background: #fff;
            color: #1a2b34;
            transition: border-color .15s;
        }
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #30596C;
            box-shadow: 0 0 0 2px rgba(48,89,108,.15);
        }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap: 1rem; }
        .form-error { font-size: .75rem; color: #dc2626; margin-top: .25rem; }

        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px,1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: #fff; border: 1px solid var(--admin-border); border-radius: .5rem; padding: 1.1rem; text-align: center; }
        .stat-card__number { font-size: 2rem; font-weight: 700; color: #30596C; }
        .stat-card__label  { font-size: .75rem; text-transform: uppercase; letter-spacing: .06em; color: #6b7f89; margin-top: .2rem; }

        /* Pagination – keep Laravel default pagination links legible */
        .pagination-wrap { margin-top: 1rem; font-size: .85rem; }
        .pagination-wrap nav { display: flex; gap: .4rem; flex-wrap: wrap; }

        /* Calendar grid */
        .cal-legend { display: flex; gap: 1.5rem; flex-wrap: wrap; font-size: .8rem; margin-bottom: 1rem; }
        .cal-legend__dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; margin-right: .3rem; }
        .dot-booked      { background: #30596C; }
        .dot-owner       { background: #9333ea; }
        .dot-maintenance { background: #f59e0b; }

        .event-list { list-style: none; }
        .event-item { display: flex; align-items: flex-start; gap: .75rem; padding: .65rem 0; border-bottom: 1px solid var(--admin-border); }
        .event-item:last-child { border-bottom: none; }
        .event-item__bar { width: 4px; min-height: 36px; border-radius: 4px; flex-shrink: 0; }
        .event-item__bar--booked { background: #30596C; }
        .event-item__bar--owner  { background: #9333ea; }
        .event-item__bar--maintenance { background: #f59e0b; }
        .event-item__dates { font-size: .8rem; color: #6b7f89; }
        .event-item__name  { font-weight: 600; font-size: .875rem; }

        /* Mobile responsive sidebar */
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .admin-sidebar {
                width: 100%;
                min-height: auto;
                height: auto;
                position: static;
                flex-direction: row;
                overflow-x: auto;
                overflow-y: visible;
            }
            .admin-sidebar__brand { white-space: nowrap; }
            .admin-nav { display: flex; flex-direction: row; padding: 0; }
            .admin-nav li a { white-space: nowrap; border-left: none; border-bottom: 3px solid transparent; }
            .admin-nav li a:hover,
            .admin-nav li a.active { border-left-color: transparent; border-bottom-color: var(--admin-sidebar-active); }
        }
    </style>
</head>
<body>
    {{-- Sidebar navigation --}}
    <aside class="admin-sidebar">
        <a href="{{ route('admin.dashboard') }}" class="admin-sidebar__brand">
            La Caracola
            <span>Pannello di controllo</span>
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
