<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class FinancialEntry extends Model
{
    protected $fillable = [
        'type',
        'category',
        'description',
        'amount',
        'entry_date',
        'tax_declaration',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'entry_date'      => 'date',
        'tax_declaration' => 'boolean',
    ];

    public function isIncome(): bool
    {
        return $this->type === 'income';
    }

    public function isExpense(): bool
    {
        return $this->type === 'expense';
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(FinancialAttachment::class, 'attachable');
    }
}
