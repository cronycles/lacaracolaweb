<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Municipality extends Model
{
    public $timestamps = false;

    protected $fillable = ['code', 'name', 'province', 'expires_at'];

    protected $casts = [
        'expires_at' => 'date',
    ];
}
