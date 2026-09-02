<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('external_calendar_providers')) {
            return;
        }

        Schema::create('external_calendar_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('url')->nullable();
            $table->boolean('enabled')->default(false);
            $table->string('sync_status', 30)->default('never_synced');
            $table->timestamp('last_sync_attempt_at')->nullable();
            $table->timestamp('last_successful_sync_at')->nullable();
            $table->unsignedInteger('imported_event_count')->default(0);
            $table->text('latest_error')->nullable();
            $table->timestamps();

            $table->index(['enabled', 'last_successful_sync_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_calendar_providers');
    }
};