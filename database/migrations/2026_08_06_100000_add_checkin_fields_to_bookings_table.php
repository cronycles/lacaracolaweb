<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('checkin_token', 64)->nullable()->unique()->after('confirmation_sent_at');
            $table->timestamp('checkin_token_expires_at')->nullable()->after('checkin_token');
            $table->timestamp('checkin_completed_at')->nullable()->after('checkin_token_expires_at');
            $table->string('locale', 5)->nullable()->after('checkin_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn(['checkin_token', 'checkin_token_expires_at', 'checkin_completed_at', 'locale']);
        });
    }
};
