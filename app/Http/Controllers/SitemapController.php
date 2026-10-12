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
            ->map(fn ($route): array => [
                'name' => $route->getName(),
                'url' => route($route->getName()),
            ])
            ->unique('url')
            ->values();

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        foreach ($urls as $entry) {
            $url = $entry['url'];
            $routeName = $entry['name'];
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.htmlspecialchars($url, ENT_XML1, 'UTF-8')."</loc>\n";
            $xml .= '    <lastmod>'.$this->lastModified((string) $routeName)."</lastmod>\n";
            $xml .= '    <priority>'.$this->priority((string) $routeName)."</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= "</urlset>\n";

        return response($xml)->header('Content-Type', 'application/xml');
    }

    private function lastModified(string $routeName): string
    {
        [$locale, $page] = explode('.', $routeName, 2);
        $viewPath = resource_path('views/public/'.str_replace('.', '/', $page).'.blade.php');
        $sourcePaths = [
            $viewPath,
            resource_path('views/layouts/app.blade.php'),
            lang_path($locale.'/app.php'),
            config_path('apartment.php'),
            __FILE__,
        ];

        $timestamps = [];
        foreach ($sourcePaths as $sourcePath) {
            if (! is_file($sourcePath)) {
                continue;
            }

            $timestamp = filemtime($sourcePath);
            if ($timestamp !== false) {
                $timestamps[] = $timestamp;
            }
        }

        return gmdate('Y-m-d', max($timestamps));
    }

    private function priority(string $routeName): string
    {
        $page = substr($routeName, strpos($routeName, '.') + 1);

        return match ($page) {
            'home' => '1.0',
            'apartment' => '0.9',
            default => '0.7',
        };
    }
}
