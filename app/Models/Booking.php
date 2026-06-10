<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'person_id',
        'checkin',
        'checkout',
        'adults',
        'children',
        'babies',
        'pets',
        'source',
        'external_ref',
        'notes',
        'canceled_at',
        'income_amount',
        'income_paid',
        'income_paid_at',
        'cleaning_amount',
        'cleaning_paid',
        'linen_amount',
        'linen_paid',
        'parking_amount',
        'parking_paid',
        'parking_paid_at',
        'services_paid_at',
        'income_tax',
        'cleaning_tax',
        'linen_tax',
        'parking_tax',
    ];

    protected $casts = [
        'checkin'          => 'date',
        'checkout'         => 'date',
        'adults'           => 'integer',
        'children'         => 'integer',
        'babies'           => 'integer',
        'pets'             => 'integer',
        'canceled_at'      => 'datetime',
        'income_amount'    => 'decimal:2',
        'income_paid'      => 'boolean',
        'income_paid_at'   => 'date',
        'cleaning_amount'  => 'decimal:2',
        'cleaning_paid'    => 'boolean',
        'linen_amount'     => 'decimal:2',
        'linen_paid'       => 'boolean',
        'parking_amount'   => 'decimal:2',
        'parking_paid'     => 'boolean',
        'parking_paid_at'  => 'date',
        'services_paid_at' => 'date',
        'income_tax'       => 'boolean',
        'cleaning_tax'     => 'boolean',
        'linen_tax'        => 'boolean',
        'parking_tax'      => 'boolean',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function availabilityBlock(): HasOne
    {
        return $this->hasOne(AvailabilityBlock::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(FinancialAttachment::class, 'attachable');
    }

    /** Guests occupying bed spaces */
    public function getTotalGuestsAttribute(): int
    {
        return $this->adults + $this->children;
    }

    /** Number of nights */
    public function getNightsAttribute(): int
    {
        return (int) $this->checkin->diffInDays($this->checkout);
    }

    /** Total expenses for this booking (cleaning + linen), null if both unknown */
    public function getTotalExpensesAttribute(): ?float
    {
        if ($this->cleaning_amount === null && $this->linen_amount === null) {
            return null;
        }

        return (float) ($this->cleaning_amount ?? 0) + (float) ($this->linen_amount ?? 0);
    }

    /** Paid income from this booking (only if marked as paid, includes parking) */
    public function getPaidIncomeAttribute(): float
    {
        $total = $this->income_paid && $this->income_amount !== null
            ? (float) $this->income_amount
            : 0;
        if ($this->parking_paid && $this->parking_amount !== null) {
            $total += (float) $this->parking_amount;
        }
        return $total;
    }

    /** Paid expenses for this booking (only paid items) */
    public function getPaidExpensesAttribute(): float
    {
        $total = 0;
        if ($this->cleaning_paid && $this->cleaning_amount !== null) {
            $total += (float) $this->cleaning_amount;
        }
        if ($this->linen_paid && $this->linen_amount !== null) {
            $total += (float) $this->linen_amount;
        }
        return $total;
    }

    public function isCanceled(): bool
    {
        return $this->canceled_at !== null;
    }
}
