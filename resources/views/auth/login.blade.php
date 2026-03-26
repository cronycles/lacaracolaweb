<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accesso — La Caracola Admin</title>
    @vite(['resources/css/app.css'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f6f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #fff;
            border: 1px solid #dde3e8;
            border-radius: .5rem;
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 380px;
            box-shadow: 0 4px 24px rgba(0,0,0,.06);
        }
        .login-brand {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-brand__name {
            font-size: 1.4rem;
            font-weight: 700;
            color: #30596C;
        }
        .login-brand__sub {
            font-size: .75rem;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-top: .2rem;
        }
        .form-group { margin-bottom: 1rem; }
        .form-label  { display: block; font-size: .8rem; font-weight: 600; color: #374151; margin-bottom: .35rem; }
        .form-input  {
            width: 100%;
            padding: .55rem .75rem;
            border: 1px solid #dde3e8;
            border-radius: .375rem;
            font-size: .875rem;
            color: #1a2b34;
            transition: border-color .15s;
        }
        .form-input:focus { outline: none; border-color: #30596C; box-shadow: 0 0 0 2px rgba(48,89,108,.15); }
        .form-error { font-size: .75rem; color: #dc2626; margin-top: .3rem; }
        .btn-submit {
            width: 100%;
            padding: .65rem;
            background: #30596C;
            color: #fff;
            border: none;
            border-radius: .375rem;
            font-size: .9rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s;
            margin-top: .5rem;
        }
        .btn-submit:hover { background: #245069; }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            border-radius: .375rem;
            padding: .65rem .9rem;
            font-size: .85rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-brand">
            <div class="login-brand__name">La Caracola</div>
            <div class="login-brand__sub">Pannello di controllo</div>
        </div>

        @if (session('status'))
            <div class="alert-error" style="background:#d1fae5;color:#065f46;border-color:#6ee7b7">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert-error">
                Credenziali non valide.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.post') }}">
            @csrf
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="form-input"
                       value="{{ old('email') }}" required autofocus autocomplete="username">
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input"
                       required autocomplete="current-password">
            </div>
            <div class="form-group" style="display:flex;align-items:center;gap:.5rem">
                <input type="checkbox" id="remember" name="remember" value="1" style="accent-color:#30596C">
                <label for="remember" class="form-label" style="margin:0;cursor:pointer">Ricordami</label>
            </div>
            <button type="submit" class="btn-submit">Accedi</button>
        </form>
    </div>
</body>
</html>
