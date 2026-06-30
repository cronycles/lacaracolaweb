<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')
                ->nullable()
                ->constrained('bookings')
                ->restrictOnDelete();
            $table->string('driver', 60);
            $table->enum('mode', ['test', 'send']);
            $table->enum('status', ['success', 'error']);
            $table->unsignedTinyInteger('guests_count');
            $table->json('guests_payload');
            $table->json('soap_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_reports');
    }
};
