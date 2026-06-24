<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name');
            $table->string('source')->nullable();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('review_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->text('text');
            $table->timestamps();

            $table->unique(['review_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_translations');
        Schema::dropIfExists('reviews');
    }
};
