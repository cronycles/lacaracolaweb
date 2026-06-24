<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    protected $fillable = ['booking_id', 'author_name', 'source', 'rating', 'is_active'];

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
        $translations = $this->translations->keyBy('locale');

        return $translations->get($locale)?->text
            ?? $translations->get('en')?->text
            ?? $translations->get('it')?->text
            ?? '';
    }
}
