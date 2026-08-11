@extends('layouts.admin')

@section('title', 'Profilo pagamenti')

@section('content')
    <div style="max-width:600px">
        <div class="a-card">
            <div class="a-card__title">Profilo pagamenti</div>
            <p style="font-size:.875rem;color:#6b7f89;margin-bottom:1.25rem">
                Questi dati vengono usati nelle email di conferma e nella ricevuta operativa.
            </p>
            <form method="POST" action="{{ route('admin.payment-profile.update') }}">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label" for="tax_code">Codice fiscale</label>
                    <input type="text" id="tax_code" name="tax_code" class="form-input" value="{{ old('tax_code', $user->tax_code) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="payment_beneficiary">Beneficiario</label>
                    <input type="text" id="payment_beneficiary" name="payment_beneficiary" class="form-input" value="{{ old('payment_beneficiary', $user->payment_beneficiary) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="payment_iban">IBAN</label>
                    <input type="text" id="payment_iban" name="payment_iban" class="form-input" value="{{ old('payment_iban', $user->payment_iban) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="payment_bic">BIC/SWIFT</label>
                    <input type="text" id="payment_bic" name="payment_bic" class="form-input" value="{{ old('payment_bic', $user->payment_bic) }}">
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                        <input type="hidden" name="payment_enabled" value="0">
                        <input type="checkbox" name="payment_enabled" value="1" @checked(old('payment_enabled', $user->payment_enabled))>
                        <span class="form-label" style="margin:0">Abilita questo host owner per i pagamenti</span>
                    </label>
                </div>
                <button type="submit" class="btn btn--primary">Salva</button>
            </form>
        </div>
    </div>
@endsection
