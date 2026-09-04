<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    public function test_sitemap_contains_public_localized_pages(): void
    {
        URL::forceRootUrl('https://lacaracolaandora.com');
        URL::forceScheme('https');

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
        $xml = new \DOMDocument;
        self::assertTrue($xml->loadXML($response->getContent()));
        $response->assertSee('https://lacaracolaandora.com/it/appartamento', false);
        $response->assertSee('https://lacaracolaandora.com/en/apartment', false);
        $response->assertSee('https://lacaracolaandora.com/fr/avis', false);
        $response->assertSee('https://lacaracolaandora.com/de/wohnung', false);
        $response->assertDontSee('/it/prenota/grazie', false);
        $response->assertDontSee('/check-in/', false);
    }
}
