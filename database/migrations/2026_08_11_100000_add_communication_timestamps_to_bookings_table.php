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
            $table->timestamp('payment_received_sent_at')->nullable()->after('confirmation_sent_at');
            $table->timestamp('checkin_reminder_sent_at')->nullable()->after('checkin_completed_at');
            $table->timestamp('telegram_notified_at')->nullable()->after('checkin_reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn([
                'payment_received_sent_at',
                'checkin_reminder_sent_at',
                'telegram_notified_at',
            ]);
        });
    }
};
