<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * People: base contact entity.
 * A person can optionally be linked to bookings (making them a guest)
 * and/or subscribed to the newsletter.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable()->unique();
            $table->string('phone', 30)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('country_code', 3)->nullable(); // ISO 3166-1 alpha-2 or alpha-3
            $table->string('document_type', 30)->nullable(); // passport, id_card, etc.
            $table->string('document_number', 60)->nullable();
            $table->boolean('newsletter_subscribed')->default(false);
            $table->timestamp('newsletter_subscribed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
