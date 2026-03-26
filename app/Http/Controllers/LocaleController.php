<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    private const SUPPORTED = ['it', 'en', 'fr', 'de'];

    /**
     * Switch the user's locale and redirect back.
     */
    public function switch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', Rule::in(self::SUPPORTED)],
        ]);

        Session::put('locale', $data['locale']);

        return redirect()->back();
    }
}
