<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // The root path redirects to the locale-aware home route (see
        // LegacyRedirectController::home()) — follow it to assert the
        // actual homepage renders successfully.
        $response = $this->get('/');

        $response->assertStatus(307);

        $this->get($response->headers->get('Location'))->assertOk();
    }
}
