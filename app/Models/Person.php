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
        'phone_prefix',
        'birth_date',
        'country_code',
        'document_type',
        'document_number',
        'newsletter_subscribed',
        'newsletter_subscribed_at',
        'newsletter_opted_out',
        // Guest reporting fields
        'gender',
        'birth_municipality',
        'birth_province',
        'birth_country_code',
        'nationality_code',
        'document_issue_place',
        'document_issue_country_code',
    ];

    protected $casts = [
        'birth_date'               => 'date',
        'newsletter_subscribed'    => 'boolean',
        'newsletter_subscribed_at' => 'datetime',
        'newsletter_opted_out'     => 'boolean',
    ];

    /** Full name helper */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getCountryDisplayAttribute(): ?string
    {
        if (! $this->country_code) {
            return null;
        }

        $countryName = Country::where('iso2', $this->country_code)->value('name_it');

        if (! $countryName) {
            return $this->country_code;
        }

        return $countryName;
    }

    public function getCountryFlagAttribute(): ?string
    {
        return $this->isoToFlag($this->country_code);
    }

    public function getNationalityDisplayAttribute(): ?string
    {
        if (! $this->nationality_code) {
            return null;
        }

        $countryName = Country::where('iso2', $this->nationality_code)->value('name_it');

        if (! $countryName) {
            return $this->nationality_code;
        }

        return $countryName;
    }

    public function getNationalityFlagAttribute(): ?string
    {
        return $this->isoToFlag($this->nationality_code);
    }

    private function isoToFlag(?string $isoCode): ?string
    {
        if (! $isoCode || strlen($isoCode) !== 2) {
            return null;
        }

        $code = strtoupper($isoCode);
        if (! ctype_alpha($code)) {
            return null;
        }

        // Regional indicator symbols: A=🇦 (U+1F1E6), offset from ord('A')=65 is 127397
        return mb_chr(127397 + ord($code[0])) . mb_chr(127397 + ord($code[1]));
    }

    public function autoSubscribeToNewsletter(): void
    {
        if ($this->newsletter_opted_out) {
            return;
        }

        $this->subscribeToNewsletter();
    }

    public function subscribeToNewsletter(): void
    {
        $this->forceFill([
            'newsletter_subscribed' => true,
            'newsletter_subscribed_at' => $this->newsletter_subscribed_at ?? now(),
            'newsletter_opted_out' => false,
        ])->save();
    }

    public function unsubscribeFromNewsletter(): void
    {
        $this->forceFill([
            'newsletter_subscribed' => false,
            'newsletter_subscribed_at' => null,
            'newsletter_opted_out' => true,
        ])->save();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
