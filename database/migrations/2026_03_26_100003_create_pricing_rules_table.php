<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pricing rules: price per night for a given date range.
 * Multiple rules can coexist; the most specific (shortest range) wins.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80); // e.g. "Alta stagione 2026"
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('price_per_night'); // in EUR cents to avoid float
            $table->unsignedTinyInteger('min_nights')->default(3);
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
