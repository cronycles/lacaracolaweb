<?php

declare(strict_types=1);

namespace App\Services\Newsletter\Sanitizer;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Restricts the `class` attribute to the exact, pre-defined values the newsletter
 * editor and email layout understand (alignment, image width presets, CTA button).
 */
final class NewsletterClassAttributeSanitizer implements AttributeSanitizerInterface
{
    private const ALLOWED_CLASSES_BY_ELEMENT = [
        'p' => ['ql-align-left', 'ql-align-center', 'ql-align-right'],
        'h1' => ['ql-align-left', 'ql-align-center', 'ql-align-right'],
        'h2' => ['ql-align-left', 'ql-align-center', 'ql-align-right'],
        'h3' => ['ql-align-left', 'ql-align-center', 'ql-align-right'],
        'a' => ['btn'],
    ];

    public function getSupportedElements(): ?array
    {
        return array_keys(self::ALLOWED_CLASSES_BY_ELEMENT);
    }

    public function getSupportedAttributes(): ?array
    {
        return ['class'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        $allowed = self::ALLOWED_CLASSES_BY_ELEMENT[$element] ?? [];
        $kept = array_values(array_intersect(preg_split('/\s+/', trim($value)) ?: [], $allowed));

        return $kept === [] ? null : implode(' ', $kept);
    }
}
