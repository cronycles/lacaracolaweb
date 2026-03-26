<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
}
