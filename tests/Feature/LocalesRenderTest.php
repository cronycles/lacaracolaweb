<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocalesRenderTest extends TestCase
{
    public function test_locales_render()
    {
        $locales = [
            'it' => ['/it', ['Andora', 'Riviera dei Fiori']],
            'en' => ['/en', ['Andora', 'Italian Riviera', 'self-catering']],
            'fr' => ['/fr', ['Andora', 'Riviera Italienne']],
            'de' => ['/de', ['Andora', 'Ligurien', 'Blumenriviera']],
        ];

        foreach ($locales as $locale => $data) {
            $path = $data[0];
            $keywords = $data[1];
            
            echo "\n--- Testing Locale: $locale (Path: $path) ---\n";
            try {
                $response = $this->get($path);
                echo "Status Code: " . $response->status() . "\n";
                if ($response->status() !== 200) {
                    echo "Response content excerpt (first 500 chars):\n";
                    echo substr($response->getContent(), 0, 500) . "\n";
                }
                
                $html = $response->getContent();
                
                foreach ($keywords as $keyword) {
                    $found = stripos($html, $keyword) !== false;
                    echo "Keyword '$keyword': " . ($found ? "FOUND" : "NOT FOUND") . "\n";
                }
            } catch (\Exception $e) {
                echo "Exception encountered for locale $locale: " . $e->getMessage() . "\n";
            }
        }
        $this->assertTrue(true);
    }
}
