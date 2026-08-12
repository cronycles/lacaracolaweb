<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Person;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createBooking(array $overrides = []): Booking
    {
        $person = Person::create([
            'first_name' => 'Anna',
            'last_name' => 'Verdi',
            'email' => 'anna.verdi@example.com',
        ]);

        return Booking::create(array_merge([
            'person_id' => $person->id,
            'checkin' => now()->subDays(5)->format('Y-m-d'),
            'checkout' => now()->subDay()->format('Y-m-d'),
            'adults' => 1,
            'source' => 'direct',
        ], $overrides));
    }

    public function test_guest_can_submit_one_hidden_review_in_selected_language(): void
    {
        $booking = $this->createBooking(['locale' => 'en']);

        $this->get(route('review.show', $booking->review_token))
            ->assertOk()
            ->assertSee(__('app.review_title'));

        $this->post(route('review.confirm', $booking->review_token), [
            'rating' => 9,
            'text' => 'A wonderful stay by the sea.',
            'liked_text' => 'The quiet terrace.',
            'disliked_text' => '',
        ])->assertRedirect(route('review.show', ['token' => $booking->review_token, 'lang' => 'en']));

        $this->assertDatabaseHas('reviews', [
            'booking_id' => $booking->id,
            'rating' => 9,
            'original_locale' => 'en',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('review_translations', [
            'locale' => 'en',
            'text' => 'A wonderful stay by the sea.',
        ]);
    }

    public function test_guest_edit_replaces_all_translations_and_hides_review(): void
    {
        $booking = $this->createBooking();
        $review = Review::create([
            'booking_id' => $booking->id,
            'author_name' => 'Anna',
            'rating' => 8,
            'is_active' => true,
            'original_locale' => 'en',
        ]);
        $review->translations()->createMany([
            ['locale' => 'en', 'text' => 'Old English text'],
            ['locale' => 'it', 'text' => 'Vecchio testo italiano'],
        ]);

        $this->post(route('review.edit', $booking->review_token))->assertRedirect();
        $this->post(route('review.confirm', $booking->review_token), [
            'rating' => 10,
            'text' => 'Nuovo testo italiano',
        ])->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'original_locale' => 'it',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('review_translations', [
            'review_id' => $review->id,
            'locale' => 'it',
            'text' => 'Nuovo testo italiano',
        ]);
        $this->assertDatabaseMissing('review_translations', [
            'review_id' => $review->id,
            'locale' => 'en',
        ]);
    }

    public function test_expired_review_link_returns_not_found(): void
    {
        $booking = $this->createBooking();
        $booking->forceFill(['review_token_expires_at' => now()->subMinute()])->save();

        $this->get(route('review.show', $booking->review_token))->assertNotFound();
    }
}
