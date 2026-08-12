<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('review_token', 64)->nullable()->unique()->after('checkin_reminder_sent_at');
            $table->timestamp('review_token_expires_at')->nullable()->after('review_token');
            $table->timestamp('review_request_sent_at')->nullable()->after('review_token_expires_at');
        });

        Schema::table('reviews', function (Blueprint $table): void {
            $table->string('original_locale', 5)->nullable()->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropColumn('original_locale');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn(['review_token', 'review_token_expires_at', 'review_request_sent_at']);
        });
    }
};
