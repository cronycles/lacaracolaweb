<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestType extends Model
{
    public $timestamps = false;

    protected $fillable = ['code', 'name_it', 'requires_document'];

    protected $casts = [
        'requires_document' => 'boolean',
    ];
}
