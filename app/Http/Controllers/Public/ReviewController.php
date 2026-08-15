<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReviewController extends Controller
{
    private const LOCALES = ['it', 'en', 'fr', 'de'];

    public function show(Request $request, string $token): View
    {
        $booking = $this->resolveBooking($token);
        App::setLocale($this->resolveLocale($request, $booking));
        $booking->load('person', 'review.translations');

        return view('public.review', [
            'booking' => $booking,
            'review' => $booking->review,
            'locales' => self::LOCALES,
        ]);
    }

    public function confirm(Request $request, string $token): RedirectResponse
    {
        $booking = $this->resolveBooking($token);
        $locale = $this->resolveLocale($request, $booking);
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:10'],
            'text' => ['required', 'string', 'min:10', 'max:10000'],
            'liked_text' => ['nullable', 'string', 'max:2000'],
            'disliked_text' => ['nullable', 'string', 'max:2000'],
            'private_comment' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($booking, $data, $locale): void {
            $review = $booking->review ?: new Review([
                'booking_id' => $booking->id,
                'author_name' => $booking->person?->first_name ?? '',
                'source' => $booking->source,
            ]);

            $review->fill([
                'rating' => $data['rating'],
                'is_active' => false,
                'original_locale' => $locale,
                'private_comment' => trim($data['private_comment'] ?? '') ?: null,
            ]);
            $review->save();
            $review->translations()->delete();
            $review->translations()->create([
                'locale' => $locale,
                'text' => trim($data['text']),
                'liked_text' => trim($data['liked_text'] ?? '') ?: null,
                'disliked_text' => trim($data['disliked_text'] ?? '') ?: null,
            ]);
        });

        session()->forget('review_editing_'.$booking->id);

        return redirect()->route('review.show', ['token' => $booking->review_token, 'lang' => $locale]);
    }

    public function beginEdit(string $token): RedirectResponse
    {
        $booking = $this->resolveBooking($token);
        session()->put('review_editing_'.$booking->id, true);

        return redirect()->route('review.show', $token);
    }

    private function resolveBooking(string $token): Booking
    {
        $booking = Booking::where('review_token', $token)->first();

        if (! $booking || $booking->isCanceled() || ! $booking->review_token_expires_at || $booking->review_token_expires_at->isPast()) {
            abort(404);
        }

        return $booking;
    }

    private function resolveLocale(Request $request, Booking $booking): string
    {
        $lang = $request->query('lang');
        if (is_string($lang) && in_array($lang, self::LOCALES, true)) {
            return $lang;
        }

        return in_array($booking->locale, self::LOCALES, true) ? $booking->locale : config('routes.fallback', 'it');
    }
}
