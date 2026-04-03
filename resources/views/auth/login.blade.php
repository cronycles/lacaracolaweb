<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accesso — La Caracola Admin</title>
    @vite(['resources/css/app.css', 'resources/css/auth/login.css'])
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
