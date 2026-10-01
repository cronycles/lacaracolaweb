<?php

declare(strict_types=1);

namespace App\Services\Newsletter\Sanitizer;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Restricts `img` sources to assets under this app's own public newsletters storage,
 * preventing embedding of third-party tracking pixels or remote images.
 */
final class NewsletterImageSourceAttributeSanitizer implements AttributeSanitizerInterface
{
    public function getSupportedElements(): ?array
    {
        return ['img'];
    }

    public function getSupportedAttributes(): ?array
    {
        return ['src'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        $allowedPrefix = rtrim(Storage::disk('public')->url('newsletters'), '/').'/';

        return str_starts_with($value, $allowedPrefix) ? $value : null;
    }
}
