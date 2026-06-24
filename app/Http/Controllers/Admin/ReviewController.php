<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    private const LOCALES = ['it', 'en', 'fr', 'de'];

    /**
     * List all bookings with their review status, ordered by checkout DESC.
     */
    public function index(): View
    {
        $bookings = Booking::with(['person', 'review'])
            ->whereNull('canceled_at')
            ->orderByDesc('checkout')
            ->get();

        return view('admin.reviews.index', compact('bookings'));
    }

    /**
     * Show the form to create a review for a specific booking.
     */
    public function create(Booking $booking): View
    {
        if ($booking->review) {
            return redirect()->route('admin.reviews.edit', $booking->review);
        }

        $review = new Review(['booking_id' => $booking->id, 'rating' => 5]);
        $review->setRelation('booking', $booking);

        // Pre-fill author_name with guest first name and source from booking
        $review->author_name = $booking->person?->first_name ?? '';
        $review->source = $booking->source ?? '';

        $translations = collect(self::LOCALES)->mapWithKeys(fn ($l) => [$l => '']);

        return view('admin.reviews.form', [
            'review'       => $review,
            'booking'      => $booking,
            'translations' => $translations,
            'locales'      => self::LOCALES,
        ]);
    }

    /**
     * Store a new review for a booking.
     */
    public function store(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'author_name'      => ['required', 'string', 'max:255'],
            'source'           => ['nullable', 'string', 'max:255'],
            'rating'           => ['required', 'integer', 'min:1', 'max:5'],
            'is_active'        => ['boolean'],
            'translations.it'  => ['required', 'string', 'min:10'],
            'translations.en'  => ['nullable', 'string', 'min:10'],
            'translations.fr'  => ['nullable', 'string', 'min:10'],
            'translations.de'  => ['nullable', 'string', 'min:10'],
        ]);

        $review = Review::create([
            'booking_id'  => $booking->id,
            'author_name' => $data['author_name'],
            'source'      => $data['source'] ?? null,
            'rating'      => $data['rating'],
            'is_active'   => $request->boolean('is_active', true),
        ]);

        foreach (self::LOCALES as $locale) {
            $text = trim($data['translations'][$locale] ?? '');
            if ($text !== '') {
                $review->translations()->create(['locale' => $locale, 'text' => $text]);
            }
        }

        return redirect()->route('admin.reviews.index')
            ->with('success', 'Recensione aggiunta.');
    }

    /**
     * Show the form to edit an existing review.
     */
    public function edit(Review $review): View
    {
        $review->load(['booking.person', 'translations']);

        $translations = collect(self::LOCALES)->mapWithKeys(
            fn ($l) => [$l => $review->translations->firstWhere('locale', $l)?->text ?? '']
        );

        return view('admin.reviews.form', [
            'review'       => $review,
            'booking'      => $review->booking,
            'translations' => $translations,
            'locales'      => self::LOCALES,
        ]);
    }

    /**
     * Update an existing review.
     */
    public function update(Request $request, Review $review): RedirectResponse
    {
        $data = $request->validate([
            'author_name'      => ['required', 'string', 'max:255'],
            'source'           => ['nullable', 'string', 'max:255'],
            'rating'           => ['required', 'integer', 'min:1', 'max:5'],
            'is_active'        => ['boolean'],
            'translations.it'  => ['required', 'string', 'min:10'],
            'translations.en'  => ['nullable', 'string', 'min:10'],
            'translations.fr'  => ['nullable', 'string', 'min:10'],
            'translations.de'  => ['nullable', 'string', 'min:10'],
        ]);

        $review->update([
            'author_name' => $data['author_name'],
            'source'      => $data['source'] ?? null,
            'rating'      => $data['rating'],
            'is_active'   => $request->boolean('is_active', true),
        ]);

        foreach (self::LOCALES as $locale) {
            $text = trim($data['translations'][$locale] ?? '');
            if ($text !== '') {
                $review->translations()->updateOrCreate(
                    ['locale' => $locale],
                    ['text' => $text]
                );
            } else {
                $review->translations()->where('locale', $locale)->delete();
            }
        }

        return redirect()->route('admin.reviews.index')
            ->with('success', 'Recensione aggiornata.');
    }

    /**
     * Delete a review (and its translations via cascade).
     */
    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return redirect()->route('admin.reviews.index')
            ->with('success', 'Recensione eliminata.');
    }
}
