<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterhomePdfImportLog extends Model
{
    protected $fillable = [
        'imported_by_user_id',
        'file_name',
        'total_rows',
        'new_rows',
        'created_rows',
        'duplicate_rows',
        'skipped_rows',
        'error_rows',
        'warnings',
        'error_details',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'new_rows' => 'integer',
        'created_rows' => 'integer',
        'duplicate_rows' => 'integer',
        'skipped_rows' => 'integer',
        'error_rows' => 'integer',
        'warnings' => 'array',
        'error_details' => 'array',
    ];

    public function importedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by_user_id');
    }
}
