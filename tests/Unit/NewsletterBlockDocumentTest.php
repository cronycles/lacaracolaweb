<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Newsletter\NewsletterBlockDocument;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NewsletterBlockDocumentTest extends TestCase
{
    public function test_supported_blocks_are_normalized(): void
    {
        $document = (new NewsletterBlockDocument)->validate([
            ['type' => 'heading', 'level' => 1, 'text' => 'Ciao'],
            ['type' => 'paragraph', 'text' => 'Testo', 'bold' => true],
            ['type' => 'list', 'items' => ['Uno', 'Due']],
            ['type' => 'separator'],
            ['type' => 'image', 'path' => 'newsletters/photo.jpg', 'alt' => 'Foto'],
            ['type' => 'button', 'text' => 'Prenota', 'url' => 'https://example.test'],
        ]);

        self::assertSame(6, count($document));
        self::assertTrue($document[1]['bold']);
        self::assertSame(['Uno', 'Due'], $document[2]['items']);
    }

    public function test_unknown_block_types_are_rejected(): void
    {
        $this->expectException(ValidationException::class);

        (new NewsletterBlockDocument)->validate([
            ['type' => 'html', 'value' => '<script>alert(1)</script>'],
        ]);
    }

    public function test_images_must_use_controlled_newsletter_paths(): void
    {
        $this->expectException(ValidationException::class);

        (new NewsletterBlockDocument)->validate([
            ['type' => 'image', 'path' => 'uploads/photo.jpg'],
        ]);
    }
}
