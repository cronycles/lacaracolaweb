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
     * Show the form to create a review for a specific booking.
     */
    public function create(Booking $booking): View
    {
        if ($booking->review) {
            return redirect()->route('admin.reviews.edit', $booking->review);
        }

        $review = new Review(['booking_id' => $booking->id, 'rating' => 10]);
        $review->setRelation('booking', $booking);

        // Pre-fill author_name with guest first name and source from booking
        $review->author_name = $booking->person?->first_name ?? '';
        $review->source = $booking->source ?? '';

        $translations = collect(self::LOCALES)->mapWithKeys(fn ($l) => [$l => '']);
        $liked = collect(self::LOCALES)->mapWithKeys(fn ($l) => [$l => '']);
        $disliked = collect(self::LOCALES)->mapWithKeys(fn ($l) => [$l => '']);

        return view('admin.reviews.form', [
            'review'       => $review,
            'booking'      => $booking,
            'translations' => $translations,
            'liked'        => $liked,
            'disliked'     => $disliked,
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
            'rating'           => ['required', 'integer', 'min:1', 'max:10'],
            'is_active'        => ['boolean'],
            'translations.it'  => ['required', 'string', 'min:10'],
            'translations.en'  => ['nullable', 'string', 'min:10'],
            'translations.fr'  => ['nullable', 'string', 'min:10'],
            'translations.de'  => ['nullable', 'string', 'min:10'],
            'liked.it'         => ['nullable', 'string', 'max:2000'],
            'liked.en'         => ['nullable', 'string', 'max:2000'],
            'liked.fr'         => ['nullable', 'string', 'max:2000'],
            'liked.de'         => ['nullable', 'string', 'max:2000'],
            'disliked.it'      => ['nullable', 'string', 'max:2000'],
            'disliked.en'      => ['nullable', 'string', 'max:2000'],
            'disliked.fr'      => ['nullable', 'string', 'max:2000'],
            'disliked.de'      => ['nullable', 'string', 'max:2000'],
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
            $likedText = trim($data['liked'][$locale] ?? '');
            $dislikedText = trim($data['disliked'][$locale] ?? '');
            if ($text !== '' || $likedText !== '' || $dislikedText !== '') {
                $review->translations()->create([
                    'locale' => $locale,
                    'text' => $text ?: null,
                    'liked_text' => $likedText ?: null,
                    'disliked_text' => $dislikedText ?: null,
                ]);
            }
        }

        return redirect()->route('admin.bookings.show', $booking)
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
        $liked = collect(self::LOCALES)->mapWithKeys(
            fn ($l) => [$l => $review->translations->firstWhere('locale', $l)?->liked_text ?? '']
        );
        $disliked = collect(self::LOCALES)->mapWithKeys(
            fn ($l) => [$l => $review->translations->firstWhere('locale', $l)?->disliked_text ?? '']
        );

        return view('admin.reviews.form', [
            'review'       => $review,
            'booking'      => $review->booking,
            'translations' => $translations,
            'liked'        => $liked,
            'disliked'     => $disliked,
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
            'rating'           => ['required', 'integer', 'min:1', 'max:10'],
            'is_active'        => ['boolean'],
            'translations.it'  => ['required', 'string', 'min:10'],
            'translations.en'  => ['nullable', 'string', 'min:10'],
            'translations.fr'  => ['nullable', 'string', 'min:10'],
            'translations.de'  => ['nullable', 'string', 'min:10'],
            'liked.it'         => ['nullable', 'string', 'max:2000'],
            'liked.en'         => ['nullable', 'string', 'max:2000'],
            'liked.fr'         => ['nullable', 'string', 'max:2000'],
            'liked.de'         => ['nullable', 'string', 'max:2000'],
            'disliked.it'      => ['nullable', 'string', 'max:2000'],
            'disliked.en'      => ['nullable', 'string', 'max:2000'],
            'disliked.fr'      => ['nullable', 'string', 'max:2000'],
            'disliked.de'      => ['nullable', 'string', 'max:2000'],
        ]);

        $review->update([
            'author_name' => $data['author_name'],
            'source'      => $data['source'] ?? null,
            'rating'      => $data['rating'],
            'is_active'   => $request->boolean('is_active', true),
        ]);

        foreach (self::LOCALES as $locale) {
            $text = trim($data['translations'][$locale] ?? '');
            $likedText = trim($data['liked'][$locale] ?? '');
            $dislikedText = trim($data['disliked'][$locale] ?? '');
            if ($text !== '' || $likedText !== '' || $dislikedText !== '') {
                $review->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'text' => $text ?: null,
                        'liked_text' => $likedText ?: null,
                        'disliked_text' => $dislikedText ?: null,
                    ]
                );
            } else {
                $review->translations()->where('locale', $locale)->delete();
            }
        }

        return redirect()->route('admin.bookings.show', $review->booking)
            ->with('success', 'Recensione aggiornata.');
    }

    /**
     * Delete a review (and its translations via cascade).
     */
    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return redirect()->route('admin.bookings.show', $review->booking_id)
            ->with('success', 'Recensione eliminata.');
    }
}
