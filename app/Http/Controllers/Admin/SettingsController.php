<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExternalCalendarProvider;
use App\Models\Setting;
use App\Models\User;
use App\Services\ExternalCalendarSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /** Show the settings page. */
    public function index(): View
    {
        $this->authorizeExternalCalendars();

        return view('admin.settings', [
            'bookingMode' => Setting::get('booking_mode', 'form'),
            'bookingExternalUrl' => Setting::get('booking_external_url', ''),
            'calendarProviders' => $this->calendarProviders(),
        ]);
    }

    /** Persist the settings. */
    public function update(Request $request): RedirectResponse
    {
        $this->authorizeExternalCalendars();

        $data = $request->validate([
            'booking_mode' => ['required', Rule::in(['form', 'external'])],
            'booking_external_url' => [
                'nullable',
                'url',
                'max:500',
                Rule::requiredIf($request->input('booking_mode') === 'external'),
            ],
        ]);

        Setting::set('booking_mode', $data['booking_mode']);
        Setting::set('booking_external_url', $data['booking_external_url'] ?? '');

        return back()->with('success', 'Impostazioni salvate con successo.');
    }

    public function updateCalendarProvider(Request $request, ExternalCalendarProvider $provider): RedirectResponse
    {
        $this->authorizeExternalCalendars();
        $this->ensureKnownCalendarProvider($provider);

        $data = $request->validate([
            'url' => ['nullable', 'url', 'max:500', Rule::requiredIf($request->boolean('enabled'))],
            'enabled' => ['boolean'],
        ]);

        $provider->update([
            'url' => $data['url'] ?? null,
            'enabled' => $request->boolean('enabled'),
        ]);

        return back()->with('success', "Calendario {$this->calendarProviderName($provider)} salvato.");
    }

    public function syncCalendarProvider(ExternalCalendarProvider $provider, ExternalCalendarSyncService $syncService): RedirectResponse
    {
        $this->authorizeExternalCalendars();
        $this->ensureKnownCalendarProvider($provider);

        $result = $syncService->syncProvider($provider);

        return back()->with(
            $result['status'] === 'success' ? 'success' : 'error',
            $result['status'] === 'success'
                ? "Calendario {$this->calendarProviderName($provider)} sincronizzato ({$result['event_count']} eventi)."
                : "Sincronizzazione {$this->calendarProviderName($provider)} non riuscita: {$result['error']}",
        );
    }

    /** Show account security page with password change form. */
    public function accountSecurity(): View
    {
        return view('admin.account-security');
    }

    public function paymentProfile(): View
    {
        abort_unless(Auth::user()->role?->name === 'host_owner', 403);

        return view('admin.payment-profile', ['user' => Auth::user()]);
    }

    public function updatePaymentProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->role?->name === 'host_owner', 403);

        $data = $request->validate([
            'tax_code' => ['nullable', 'string', 'max:16'],
            'address_street' => ['nullable', 'string', 'max:255'],
            'address_zip' => ['nullable', 'string', 'max:10'],
            'address_city' => ['nullable', 'string', 'max:255'],
            'payment_beneficiary' => ['nullable', 'string', 'max:255'],
            'payment_iban' => ['nullable', 'string', 'max:34'],
            'payment_bic' => ['nullable', 'string', 'max:11'],
            'payment_enabled' => ['boolean'],
        ]);

        $user->update([
            'tax_code' => $data['tax_code'] ?? null,
            'address_street' => $data['address_street'] ?? null,
            'address_zip' => $data['address_zip'] ?? null,
            'address_city' => $data['address_city'] ?? null,
            'payment_beneficiary' => $data['payment_beneficiary'] ?? null,
            'payment_iban' => $data['payment_iban'] ?? null,
            'payment_bic' => $data['payment_bic'] ?? null,
            'payment_enabled' => (bool) ($data['payment_enabled'] ?? false),
        ]);

        return back()->with('success', 'Dati di pagamento salvati.');
    }

    /** Update the user's password and log out. */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Validate password change request
        $request->validate([
            'current_password' => [
                'required',
                'current_password',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                function ($attribute, $value, $fail) use ($user) {
                    // Ensure new password is different from old one
                    if (Hash::check($value, $user->password)) {
                        $fail(__('validation.password_different'));
                    }
                },
            ],
            'password_confirmation' => 'required|same:password',
        ]);

        // Update the password
        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        // Invalidate current session and log out
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login')
            ->with('success', __('app.admin_password_success'));
    }

    /** @return Collection<int, ExternalCalendarProvider> */
    private function calendarProviders(): Collection
    {
        foreach (array_keys((array) config('apartment.calendar.providers', [])) as $key) {
            ExternalCalendarProvider::firstOrCreate(['key' => $key]);
        }

        return ExternalCalendarProvider::query()
            ->whereIn('key', array_keys((array) config('apartment.calendar.providers', [])))
            ->orderBy('key')
            ->get();
    }

    private function authorizeExternalCalendars(): void
    {
        abort_unless(Auth::user() instanceof User && in_array(Auth::user()->role?->name, ['host_owner', 'super_admin'], true), 403);
    }

    private function ensureKnownCalendarProvider(ExternalCalendarProvider $provider): void
    {
        abort_unless(array_key_exists($provider->key, (array) config('apartment.calendar.providers', [])), 404);
    }

    private function calendarProviderName(ExternalCalendarProvider $provider): string
    {
        return (string) config("apartment.calendar.providers.{$provider->key}", $provider->key);
    }
}
