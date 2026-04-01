@extends('layouts.admin')

@section('title', __('app.admin_account_title'))

@section('content')
    <div style="max-width:600px">

        {{-- Success message --}}
        @if (session('success'))
            <div style="background:#d1fae5;border:1px solid #6ee7b7;border-radius:8px;padding:.75rem 1rem;margin-bottom:1.25rem;font-size:.875rem;color:#065f46">
                ✓ {{ session('success') }}
            </div>
        @endif

        {{-- Account security card --}}
        <div class="a-card">
            <div class="a-card__title">{{ __('app.admin_account_title') }}</div>

            <p style="font-size:.875rem;color:#6b7f89;margin-bottom:1.25rem">
                {{ __('app.admin_account_subtitle') }}
            </p>

            <form method="POST" action="{{ route('admin.account-security.update-password') }}">
                @csrf

                {{-- Current password field --}}
                <div class="form-group">
                    <label class="form-label" for="current_password">{{ __('app.admin_password_current') }}</label>
                    <input type="password"
                           id="current_password"
                           name="current_password"
                           class="form-input {{ $errors->has('current_password') ? 'is-invalid' : '' }}"
                           required>
                    @error('current_password')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- New password field --}}
                <div class="form-group">
                    <label class="form-label" for="password">{{ __('app.admin_password_new') }}</label>
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                           required>
                    @error('password')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                    <div style="font-size:.78rem;color:#6b7f89;margin-top:.35rem">
                        {{ __('app.admin_password_requirements') }}
                    </div>
                </div>

                {{-- Confirm password field --}}
                <div class="form-group">
                    <label class="form-label" for="password_confirmation">{{ __('app.admin_password_confirm') }}</label>
                    <input type="password"
                           id="password_confirmation"
                           name="password_confirmation"
                           class="form-input {{ $errors->has('password_confirmation') ? 'is-invalid' : '' }}"
                           required>
                    @error('password_confirmation')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn--primary">{{ __('app.admin_password_submit') }}</button>

                <a href="{{ route('admin.settings') }}" class="btn btn--secondary" style="margin-left:.5rem">
                    Annulla
                </a>
            </form>
        </div>

        {{-- Info box --}}
        <div style="background:#f3f4f6;border-radius:8px;padding:1rem;margin-top:1.5rem;font-size:.85rem;color:#6b7f89;border-left:4px solid #30596C">
            <strong style="color:#1a1a1a;display:block;margin-bottom:.5rem">ℹ️ Nota di sicurezza</strong>
            <p style="margin:0">Dopo il cambio password, verrai disconnesso. Effettua il login con la nuova password per accedere all'area amministrativa.</p>
        </div>

    </div>
@endsection
