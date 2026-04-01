<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /** Show the settings page. */
    public function index(): View
    {
        return view('admin.settings', [
            'bookingMode'        => Setting::get('booking_mode', 'form'),
            'bookingExternalUrl' => Setting::get('booking_external_url', ''),
        ]);
    }

    /** Persist the settings. */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'booking_mode'         => ['required', Rule::in(['form', 'external'])],
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

    /** Show account security page with password change form. */
    public function accountSecurity(): View
    {
        return view('admin.account-security');
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
}
