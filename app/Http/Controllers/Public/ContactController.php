<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\ContactMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        return view('public.contact');
    }

    public function send(Request $request): RedirectResponse
    {
        // Honeypot: if filled, silently discard (bot submission)
        if ($request->filled('website')) {
            return redirect()->back();
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        try {
            Mail::to(config('apartment.email'))->send(new ContactMail($data));
        } catch (\Throwable $e) {
            Log::error('ContactMail failed to send', [
                'error' => $e->getMessage(),
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['_form' => __('app.error_server')]);
        }

        return redirect()->back()->with('contact_sent', true);
    }
}
