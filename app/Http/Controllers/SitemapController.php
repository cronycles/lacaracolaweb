<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect(Route::getRoutes())
            ->filter(function ($route): bool {
                $name = $route->getName();

                return in_array('GET', $route->methods(), true)
                    && is_string($name)
                    && preg_match('/^(it|en|fr|de)\./', $name) === 1
                    && ! str_contains($name, '.booking.thanks')
                    && ! str_contains($route->uri(), '{');
            })
            ->map(fn ($route): string => route($route->getName()))
            ->unique()
            ->values();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>\n';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n';

        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.htmlspecialchars($url, ENT_XML1, 'UTF-8').'</loc></url>\n';
        }

        $xml .= '</urlset>\n';

        return response($xml)->header('Content-Type', 'application/xml');
    }
}