<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('income_paid')->default(false)->after('income_amount');
            $table->boolean('cleaning_paid')->default(false)->after('cleaning_amount');
            $table->boolean('linen_paid')->default(false)->after('linen_amount');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['income_paid', 'cleaning_paid', 'linen_paid']);
        });
    }
};
