<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewTranslation extends Model
{
    protected $fillable = ['review_id', 'locale', 'text'];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
