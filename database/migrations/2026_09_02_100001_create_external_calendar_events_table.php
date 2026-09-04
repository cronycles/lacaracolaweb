<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('external_calendar_events')) {
            return;
        }

        Schema::create('external_calendar_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('external_calendar_provider_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('external_uid');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->unique(['external_calendar_provider_id', 'external_uid']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_calendar_events');
    }
};
