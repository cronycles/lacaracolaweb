<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    protected $fillable = ['booking_id', 'author_name', 'source', 'rating', 'is_active', 'original_locale', 'private_comment'];

    protected $casts = [
        'rating'    => 'integer',
        'is_active' => 'boolean',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ReviewTranslation::class);
    }

    /**
     * Return the review text for the given locale using fallback logic:
     *   1. Exact locale match
     *   2. English (if available)
     *   3. Italian (always exists — mandatory)
     */
    public function textForLocale(string $locale): string
    {
        return $this->translationFieldForLocale($locale, 'text');
    }

    public function likedTextForLocale(string $locale): string
    {
        return $this->translationFieldForLocale($locale, 'liked_text');
    }

    public function dislikedTextForLocale(string $locale): string
    {
        return $this->translationFieldForLocale($locale, 'disliked_text');
    }

    private function translationFieldForLocale(string $locale, string $field): string
    {
        $translations = $this->translations->keyBy('locale');

        return $translations->get($locale)?->{$field}
            ?? $translations->get('en')?->{$field}
            ?? $translations->get('it')?->{$field}
            ?? '';
    }
}
