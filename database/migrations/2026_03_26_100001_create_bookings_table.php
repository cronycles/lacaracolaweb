<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bookings: each stay linked to a primary guest (person).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->restrictOnDelete();
            $table->date('checkin');
            $table->date('checkout');
            $table->unsignedTinyInteger('adults')->default(1);
            $table->unsignedTinyInteger('children')->default(0);
            $table->unsignedTinyInteger('babies')->default(0);
            $table->string('source', 30)->default('direct'); // direct, airbnb, booking, interhome
            $table->string('external_ref', 60)->nullable(); // Interhome reservation number
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Prevent double-booking
            $table->index(['checkin', 'checkout']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
