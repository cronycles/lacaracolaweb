<?php

namespace Tests\Feature;

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
}
