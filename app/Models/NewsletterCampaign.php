<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsletterCampaign extends Model
{
    protected $fillable = [
        'newsletter_template_id', 'created_by', 'title', 'subject', 'content_it', 'content_en',
        'status', 'total_recipients', 'sent_count', 'failed_count', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'content_it' => 'array', 'content_en' => 'array',
            'started_at' => 'datetime', 'completed_at' => 'datetime',
            'total_recipients' => 'integer', 'sent_count' => 'integer', 'failed_count' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(NewsletterTemplate::class, 'newsletter_template_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NewsletterDelivery::class);
    }
}