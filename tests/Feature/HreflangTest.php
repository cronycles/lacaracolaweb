<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class HreflangTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        URL::forceRootUrl('https://lacaracolaandora.com');
        URL::forceScheme('https');
    }

    public function test_internal_page_contains_equivalent_localized_hreflang_urls(): void
    {
        $response = $this->get('/it/appartamento');

        $response->assertOk();
        $response->assertSee('<link rel="alternate" hreflang="it" href="https://lacaracolaandora.com/it/appartamento">', false);
        $response->assertSee('<link rel="alternate" hreflang="en" href="https://lacaracolaandora.com/en/apartment">', false);
        $response->assertSee('<link rel="alternate" hreflang="fr" href="https://lacaracolaandora.com/fr/appartement">', false);
        $response->assertSee('<link rel="alternate" hreflang="de" href="https://lacaracolaandora.com/de/wohnung">', false);
        $response->assertSee('<link rel="alternate" hreflang="x-default" href="https://lacaracolaandora.com/it/appartamento">', false);
        $response->assertDontSee('hreflang="en" href="https://lacaracolaandora.com/en"', false);
    }

    public function test_home_contains_localized_home_hreflang_urls(): void
    {
        $response = $this->get('/en');

        $response->assertOk();
        $response->assertSee('hreflang="it" href="https://lacaracolaandora.com/it"', false);
        $response->assertSee('hreflang="en" href="https://lacaracolaandora.com/en"', false);
        $response->assertSee('hreflang="fr" href="https://lacaracolaandora.com/fr"', false);
        $response->assertSee('hreflang="de" href="https://lacaracolaandora.com/de"', false);
        $response->assertSee('hreflang="x-default" href="https://lacaracolaandora.com/it"', false);
    }

    public function test_transactional_thank_you_page_has_no_hreflang_links(): void
    {
        $response = $this->get('/it/prenota/grazie');

        $response->assertOk();
        $response->assertDontSee('hreflang=', false);
    }
}
