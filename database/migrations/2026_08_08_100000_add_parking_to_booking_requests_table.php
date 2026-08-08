<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->boolean('parking_requested')->default(false)->after('pets');
            $table->decimal('estimated_parking_amount', 8, 2)->nullable()->after('estimated_linen_amount');
        });
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->dropColumn(['parking_requested', 'estimated_parking_amount']);
        });
    }
};
