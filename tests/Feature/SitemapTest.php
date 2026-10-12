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

        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace('sm', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        self::assertSame(0, $xpath->query('//sm:url[not(sm:lastmod) or not(sm:priority)]')->length);
        foreach ($xpath->query('//sm:url/sm:lastmod') as $lastmod) {
            self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $lastmod->nodeValue);
        }

        foreach (['it', 'en', 'fr', 'de'] as $locale) {
            $homePriority = $xpath->query("//sm:url[sm:loc='https://lacaracolaandora.com/{$locale}']/sm:priority")->item(0);
            $apartmentPath = match ($locale) {
                'it' => 'appartamento',
                'en' => 'apartment',
                'fr' => 'appartement',
                'de' => 'wohnung',
            };
            $apartmentPriority = $xpath->query("//sm:url[sm:loc='https://lacaracolaandora.com/{$locale}/{$apartmentPath}']/sm:priority")->item(0);

            self::assertNotNull($homePriority);
            self::assertNotNull($apartmentPriority);
            self::assertSame('1.0', $homePriority->nodeValue);
            self::assertSame('0.9', $apartmentPriority->nodeValue);
        }
    }
}
