<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsletterTemplate extends Model
{
    protected $fillable = ['created_by', 'title', 'subject', 'content_it', 'content_en', 'archived_at'];

    protected function casts(): array
    {
        return ['content_it' => 'string', 'content_en' => 'string', 'archived_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(NewsletterCampaign::class);
    }
}