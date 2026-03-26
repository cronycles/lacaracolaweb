<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'birth_date',
        'country_code',
        'document_type',
        'document_number',
        'newsletter_subscribed',
        'newsletter_subscribed_at',
    ];

    protected $casts = [
        'birth_date'               => 'date',
        'newsletter_subscribed'    => 'boolean',
        'newsletter_subscribed_at' => 'datetime',
    ];

    /** Full name helper */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
