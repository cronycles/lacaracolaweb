<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedTinyInteger('rating')->default(10)->change();
        });

        Schema::table('review_translations', function (Blueprint $table) {
            $table->text('text')->nullable()->change();
            $table->text('liked_text')->nullable();
            $table->text('disliked_text')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('review_translations', function (Blueprint $table) {
            $table->dropColumn(['liked_text', 'disliked_text']);
            $table->text('text')->nullable(false)->change();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedTinyInteger('rating')->default(5)->change();
        });
    }
};
