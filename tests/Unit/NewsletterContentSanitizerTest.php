<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Newsletter\NewsletterContentSanitizer;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NewsletterContentSanitizerTest extends TestCase
{
    private function imageSrc(string $filename): string
    {
        return rtrim(Storage::disk('public')->url('newsletters'), '/').'/'.$filename;
    }

    public function test_allowed_tags_classes_and_attributes_pass_through(): void
    {
        $src = $this->imageSrc('photo.jpg');
        $html = '<h1 class="ql-align-center">Titolo</h1>'
            .'<p class="ql-align-right"><strong>Grassetto</strong> <em>corsivo</em> <u>sottolineato</u></p>'
            .'<ul><li>Uno</li><li>Due</li></ul>'
            .'<a href="https://example.test" class="btn">Prenota</a>'
            .'<img src="'.$src.'" alt="Foto" width="50%">'
            .'<hr>';

        $sanitized = (new NewsletterContentSanitizer)->sanitize($html);

        self::assertStringContainsString('<h1 class="ql-align-center">Titolo</h1>', $sanitized);
        self::assertStringContainsString('<strong>Grassetto</strong>', $sanitized);
        self::assertStringContainsString('<li>Uno</li>', $sanitized);
        self::assertStringContainsString('class="btn"', $sanitized);
        self::assertStringContainsString('href="https://example.test"', $sanitized);
        self::assertStringContainsString('src="'.$src.'"', $sanitized);
        self::assertStringContainsString('width="50%"', $sanitized);
        self::assertStringContainsString('<hr', $sanitized);
    }

    public function test_disallowed_tags_and_attributes_are_stripped(): void
    {
        $sanitized = (new NewsletterContentSanitizer)->sanitize(
            '<script>alert(1)</script><p style="color:red" onclick="alert(1)">Testo</p><iframe src="https://evil.test"></iframe>'
        );

        self::assertStringNotContainsString('<script', $sanitized);
        self::assertStringNotContainsString('<iframe', $sanitized);
        self::assertStringNotContainsString('style=', $sanitized);
        self::assertStringNotContainsString('onclick', $sanitized);
        self::assertStringContainsString('Testo', $sanitized);
    }

    public function test_unknown_class_values_are_dropped(): void
    {
        $sanitized = (new NewsletterContentSanitizer)->sanitize('<p class="ql-align-center evil-class">Testo</p>');

        self::assertStringContainsString('class="ql-align-center"', $sanitized);
        self::assertStringNotContainsString('evil-class', $sanitized);
    }

    public function test_image_src_outside_newsletter_storage_is_rejected(): void
    {
        $sanitized = (new NewsletterContentSanitizer)->sanitize('<img src="https://evil.test/tracker.png" alt="x">');

        self::assertStringNotContainsString('evil.test', $sanitized);
    }

    public function test_unsupported_image_width_is_rejected(): void
    {
        $src = $this->imageSrc('photo.jpg');
        $sanitized = (new NewsletterContentSanitizer)->sanitize('<img src="'.$src.'" width="999px">');

        self::assertStringNotContainsString('999px', $sanitized);
    }

    public function test_disallowed_url_schemes_are_rejected(): void
    {
        $sanitized = (new NewsletterContentSanitizer)->sanitize('<a href="javascript:alert(1)">Click</a>');

        self::assertStringNotContainsString('javascript:', $sanitized);
    }
}
