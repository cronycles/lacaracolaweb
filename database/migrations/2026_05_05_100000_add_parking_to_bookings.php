<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Costo posto auto (ingresso) associato alla prenotazione (default 10€/notte)
            $table->decimal('parking_amount', 8, 2)->nullable()->after('linen_paid');
            // Posto auto marcato come incassato (default: false)
            $table->boolean('parking_paid')->default(false)->after('parking_amount');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['parking_amount', 'parking_paid']);
        });
    }
};
