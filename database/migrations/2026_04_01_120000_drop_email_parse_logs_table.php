<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('email_parse_logs');
    }

    public function down(): void
    {
        Schema::create('email_parse_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('message_uid', 100)->unique();
            $table->string('from_address', 255)->default('');
            $table->string('subject', 500)->nullable();
            $table->timestamp('received_at')->nullable();
            $table->string('status', 20)->default('skipped');
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }
};
