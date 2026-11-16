<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('income_includes_cleaning')->default(false);
            $table->boolean('income_includes_linen')->default(false);
            $table->boolean('income_includes_parking')->default(false);
        });

        DB::table('bookings')->update([
            'income_includes_cleaning' => DB::raw('cleaning_paid'),
            'income_includes_linen' => DB::raw('linen_paid'),
        ]);
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['income_includes_cleaning', 'income_includes_linen', 'income_includes_parking']);
        });
    }
};
