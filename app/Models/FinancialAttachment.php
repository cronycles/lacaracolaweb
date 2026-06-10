<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinancialAttachment extends Model
{
    protected $fillable = [
        'original_name',
        'stored_path',
        'mime_type',
        'size',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
