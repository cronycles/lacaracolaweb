<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSuppression extends Model
{
    protected $fillable = ['email', 'suppressed_at'];

    protected function casts(): array
    {
        return ['suppressed_at' => 'datetime'];
    }
}