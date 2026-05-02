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
            // Amount received from the guest for this booking (nullable: unknown for old records).
            $table->decimal('income_amount', 8, 2)->nullable()->after('notes');
            // Cleaning cost charged for this booking (nullable: unknown for old records).
            $table->decimal('cleaning_amount', 8, 2)->nullable()->after('income_amount');
            // Linen cost for this booking (nullable: unknown for old records).
            $table->decimal('linen_amount', 8, 2)->nullable()->after('cleaning_amount');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['income_amount', 'cleaning_amount', 'linen_amount']);
        });
    }
};
