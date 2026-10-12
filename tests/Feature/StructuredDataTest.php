<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Person;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StructuredDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_localized_home_exposes_valid_vacation_rental_json_ld(): void
    {
        foreach (['it', 'en', 'fr', 'de'] as $locale) {
            $response = $this->get('/'.$locale);

            $response->assertOk();
            preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $response->getContent(), $matches);

            $this->assertArrayHasKey(1, $matches);
            $schema = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame('VacationRental', $schema['@type']);
            $this->assertSame('la-caracola-andora', $schema['identifier']);
            $this->assertSame(3, $schema['containsPlace']['numberOfRooms']);
            $this->assertSame(80, $schema['containsPlace']['floorSize']['value']);
            $this->assertCount(3, $schema['containsPlace']['bed']);
            $this->assertGreaterThanOrEqual(8, count($schema['image']));
            $this->assertStringContainsString('/'.$locale, $schema['url']);
        }
    }

    public function test_reviews_page_schema_only_includes_publicly_visible_reviews(): void
    {
        $firstReview = $this->createReview(1, 9, true, 'A lovely stay by the sea.');
        $secondReview = $this->createReview(2, 7, true, 'Comfortable apartment and friendly hosts.');
        $hiddenReview = $this->createReview(3, 10, false, 'This review is not public.');

        $response = $this->get(route('en.reviews'))
            ->assertOk()
            ->assertSee('A lovely stay by the sea.')
            ->assertSee('Comfortable apartment and friendly hosts.')
            ->assertDontSee('This review is not public.');

        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $response->getContent(), $matches);
        $this->assertArrayHasKey(1, $matches);
        $schema = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(2, $schema['aggregateRating']['reviewCount']);
        $this->assertEquals(8, $schema['aggregateRating']['ratingValue']);
        $this->assertSame(10, $schema['aggregateRating']['bestRating']);
        $this->assertSame(1, $schema['aggregateRating']['worstRating']);
        $this->assertCount(2, $schema['review']);
        $this->assertEqualsCanonicalizing(
            ['A lovely stay by the sea.', 'Comfortable apartment and friendly hosts.'],
            array_column($schema['review'], 'reviewBody'),
        );
        $this->assertNotContains('This review is not public.', array_column($schema['review'], 'reviewBody'));
    }

    public function test_reviews_page_omits_rating_schema_when_there_are_no_public_reviews(): void
    {
        $response = $this->get(route('en.reviews'))->assertOk();

        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $response->getContent(), $matches);
        $this->assertArrayHasKey(1, $matches);
        $schema = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayNotHasKey('aggregateRating', $schema);
        $this->assertArrayNotHasKey('review', $schema);
    }

    private function createReview(int $guestNumber, int $rating, bool $isActive, string $text): Review
    {
        $person = Person::create([
            'first_name' => 'Guest '.$guestNumber,
            'last_name' => 'Test',
            'email' => 'guest'.$guestNumber.'@example.com',
        ]);
        $booking = Booking::create([
            'person_id' => $person->id,
            'checkin' => now()->subDays(4)->format('Y-m-d'),
            'checkout' => now()->subDays(2)->format('Y-m-d'),
            'adults' => 1,
            'source' => 'direct',
        ]);
        $review = Review::create([
            'booking_id' => $booking->id,
            'author_name' => 'Guest '.$guestNumber,
            'rating' => $rating,
            'is_active' => $isActive,
            'original_locale' => 'en',
        ]);
        $review->translations()->create(['locale' => 'en', 'text' => $text]);

        return $review;
    }
}
