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
    /** Defaults documented in openspec/changes/tax-gross-up-pricing/design.md, Decision 1. */
    private const PRICING_SETTING_DEFAULTS = [
        'pricing_tax_rate' => '0.21',
        'pricing_tax_gross_up_items' => '["cleaning","linen"]',
        'pricing_commission_airbnb' => '0.155',
        'pricing_commission_booking' => '0.165',
        'pricing_commission_hometogo' => '0.155',
        'pricing_weekly_discount_percent' => '0.10',
        'pricing_monthly_discount_percent' => '0.20',
        // Fixed literal (not config-sourced), per ota-portal-guest-tiered-pricing/design.md, Decision 5.
        'pricing_extra_guest_fee' => '12',
    ];

    /** Show the settings page. */
    public function index(): View
    {
        $this->authorizeExternalCalendars();

        return view('admin.settings', [
            'bookingMode' => Setting::get('booking_mode', 'form'),
            'bookingExternalUrl' => Setting::get('booking_external_url', ''),
            'calendarProviders' => $this->calendarProviders(),
            'pricingSettings' => $this->pricingSettings(),
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

    /** Persist tax/portal-commission/length-discount pricing settings. */
    public function updatePricing(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pricing_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'pricing_tax_gross_up_items' => ['array'],
            'pricing_tax_gross_up_items.*' => [Rule::in(['cleaning', 'linen', 'parking'])],
            'pricing_commission_airbnb' => ['required', 'numeric', 'min:0', 'max:100'],
            'pricing_commission_booking' => ['required', 'numeric', 'min:0', 'max:100'],
            'pricing_commission_hometogo' => ['required', 'numeric', 'min:0', 'max:100'],
            'pricing_weekly_discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'pricing_monthly_discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'pricing_cleaning_fee' => ['required', 'integer', 'min:0', 'max:99999'],
            'pricing_linen_fee_per_person' => ['required', 'integer', 'min:0', 'max:99999'],
            'pricing_min_nights' => ['required', 'integer', 'min:1', 'max:' . max(1, ((int) config('apartment.booking.max_nights', 28)) - 1)],
            'pricing_extra_guest_fee' => ['required', 'integer', 'min:0', 'max:99999'],
        ]);

        Setting::set('pricing_tax_rate', (string) ($data['pricing_tax_rate'] / 100));
        Setting::set('pricing_tax_gross_up_items', json_encode(array_values($data['pricing_tax_gross_up_items'] ?? [])));
        Setting::set('pricing_commission_airbnb', (string) ($data['pricing_commission_airbnb'] / 100));
        Setting::set('pricing_commission_booking', (string) ($data['pricing_commission_booking'] / 100));
        Setting::set('pricing_commission_hometogo', (string) ($data['pricing_commission_hometogo'] / 100));
        Setting::set('pricing_weekly_discount_percent', (string) ($data['pricing_weekly_discount_percent'] / 100));
        Setting::set('pricing_monthly_discount_percent', (string) ($data['pricing_monthly_discount_percent'] / 100));
        Setting::set('pricing_cleaning_fee', (string) $data['pricing_cleaning_fee']);
        Setting::set('pricing_linen_fee_per_person', (string) $data['pricing_linen_fee_per_person']);
        Setting::set('pricing_min_nights', (string) $data['pricing_min_nights']);
        Setting::set('pricing_extra_guest_fee', (string) $data['pricing_extra_guest_fee']);

        return back()->with('success', 'Impostazioni di fiscalità e prezzi salvate.');
    }

    /** @return array{tax_rate: float, tax_gross_up_items: list<string>, commission_airbnb: float, commission_booking: float, commission_hometogo: float, weekly_discount_percent: float, monthly_discount_percent: float, cleaning_fee: int, linen_fee_per_person: int, min_nights: int, extra_guest_fee: int} */
    private function pricingSettings(): array
    {
        $items = json_decode((string) Setting::get('pricing_tax_gross_up_items', self::PRICING_SETTING_DEFAULTS['pricing_tax_gross_up_items']), true);

        return [
            'tax_rate' => (float) Setting::get('pricing_tax_rate', self::PRICING_SETTING_DEFAULTS['pricing_tax_rate']),
            'tax_gross_up_items' => is_array($items) ? array_values($items) : ['cleaning', 'linen'],
            'commission_airbnb' => (float) Setting::get('pricing_commission_airbnb', self::PRICING_SETTING_DEFAULTS['pricing_commission_airbnb']),
            'commission_booking' => (float) Setting::get('pricing_commission_booking', self::PRICING_SETTING_DEFAULTS['pricing_commission_booking']),
            'commission_hometogo' => (float) Setting::get('pricing_commission_hometogo', self::PRICING_SETTING_DEFAULTS['pricing_commission_hometogo']),
            'weekly_discount_percent' => (float) Setting::get('pricing_weekly_discount_percent', self::PRICING_SETTING_DEFAULTS['pricing_weekly_discount_percent']),
            'monthly_discount_percent' => (float) Setting::get('pricing_monthly_discount_percent', self::PRICING_SETTING_DEFAULTS['pricing_monthly_discount_percent']),
            // Defaults sourced from apartment.php (real cleaning/linen/min-stay values), not a fixed literal like pricing_extra_guest_fee.
            'cleaning_fee' => (int) Setting::get('pricing_cleaning_fee', (string) config('apartment.booking.cleaning_fee', 100)),
            'linen_fee_per_person' => (int) Setting::get('pricing_linen_fee_per_person', (string) config('apartment.booking.linen_fee_per_person', 25)),
            'min_nights' => (int) Setting::get('pricing_min_nights', (string) config('apartment.booking.min_nights', 3)),
            'extra_guest_fee' => (int) Setting::get('pricing_extra_guest_fee', self::PRICING_SETTING_DEFAULTS['pricing_extra_guest_fee']),
        ];
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
