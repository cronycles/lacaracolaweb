<?php

declare(strict_types=1);

namespace App\Services\Newsletter\Sanitizer;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Restricts the `width` attribute on newsletter images to the editor's four preset
 * sizes. Using the HTML `width` attribute (not CSS) keeps resizing visible to email
 * clients such as Outlook that otherwise ignore image CSS.
 */
final class NewsletterImageWidthAttributeSanitizer implements AttributeSanitizerInterface
{
    private const ALLOWED_WIDTHS = ['25%', '50%', '75%', '100%'];

    public function getSupportedElements(): ?array
    {
        return ['img'];
    }

    public function getSupportedAttributes(): ?array
    {
        return ['width'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        return in_array($value, self::ALLOWED_WIDTHS, true) ? $value : null;
    }
}
