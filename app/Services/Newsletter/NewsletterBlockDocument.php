<?php

declare(strict_types=1);

namespace App\Services\Newsletter;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class NewsletterBlockDocument
{
    /** @return array<int, array<string, mixed>> */
    public function validate(mixed $document): array
    {
        if (! is_array($document)) {
            throw ValidationException::withMessages(['content' => 'Il contenuto deve essere una lista di blocchi.']);
        }

        $normalized = [];
        foreach (array_values($document) as $index => $block) {
            if (! is_array($block) || ! is_string($block['type'] ?? null)) {
                throw ValidationException::withMessages(["content.{$index}" => 'Blocco newsletter non valido.']);
            }

            $type = $block['type'];
            $rules = match ($type) {
                'paragraph', 'heading' => ['text' => ['required', 'string', 'max:10000']],
                'list' => ['items' => ['required', 'array', 'min:1', 'max:100']],
                'separator' => [],
                'image' => ['path' => ['required', 'string', 'starts_with:newsletters/'], 'alt' => ['nullable', 'string', 'max:255']],
                'button' => ['text' => ['required', 'string', 'max:120'], 'url' => ['required', 'url', 'max:2048']],
                default => null,
            };

            if ($rules === null) {
                throw ValidationException::withMessages(["content.{$index}.type" => 'Tipo di blocco non supportato.']);
            }

            $validated = Validator::validate($block, $rules);
            $normalizedBlock = ['type' => $type] + $validated;
            if ($type === 'heading') {
                $normalizedBlock['level'] = in_array((int) ($block['level'] ?? 2), [1, 2, 3], true)
                    ? (int) $block['level'] : 2;
            }
            if ($type === 'paragraph' || $type === 'heading') {
                $normalizedBlock['bold'] = (bool) ($block['bold'] ?? false);
                $normalizedBlock['italic'] = (bool) ($block['italic'] ?? false);
                $normalizedBlock['underline'] = (bool) ($block['underline'] ?? false);
            }
            if ($type === 'list') {
                $normalizedBlock['items'] = array_values(array_filter($block['items'], 'is_string'));
            }
            $normalized[] = $normalizedBlock;
        }

        return $normalized;
    }
}