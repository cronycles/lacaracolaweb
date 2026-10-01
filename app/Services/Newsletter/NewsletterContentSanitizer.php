<?php

declare(strict_types=1);

namespace App\Services\Newsletter;

use App\Services\Newsletter\Sanitizer\NewsletterClassAttributeSanitizer;
use App\Services\Newsletter\Sanitizer\NewsletterImageSourceAttributeSanitizer;
use App\Services\Newsletter\Sanitizer\NewsletterImageWidthAttributeSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Sanitizes newsletter rich-text content to a strict allow-list of tags, attributes,
 * and `class` values. This is the authoritative defense against stored XSS: it runs
 * on every save regardless of what the browser-side editor produced.
 */
class NewsletterContentSanitizer
{
    public function sanitize(string $html): string
    {
        return $this->sanitizer()->sanitize($html);
    }

    private function sanitizer(): HtmlSanitizer
    {
        $config = (new HtmlSanitizerConfig())
            ->allowElement('p', ['class'])
            ->allowElement('h1', ['class'])
            ->allowElement('h2', ['class'])
            ->allowElement('h3', ['class'])
            ->allowElement('strong')
            ->allowElement('em')
            ->allowElement('u')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('a', ['href', 'class'])
            ->allowElement('img', ['src', 'alt', 'width'])
            ->allowElement('hr')
            ->allowElement('br')
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowRelativeLinks()
            ->allowMediaSchemes(['http', 'https'])
            ->allowRelativeMedias()
            ->withAttributeSanitizer(new NewsletterClassAttributeSanitizer())
            ->withAttributeSanitizer(new NewsletterImageSourceAttributeSanitizer())
            ->withAttributeSanitizer(new NewsletterImageWidthAttributeSanitizer())
            ->withMaxInputLength(200_000);

        return new HtmlSanitizer($config);
    }
}
