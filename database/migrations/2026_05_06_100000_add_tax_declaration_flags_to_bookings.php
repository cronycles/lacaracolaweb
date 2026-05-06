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
            $table->boolean('income_tax')->default(false)->after('income_paid_at');
            $table->boolean('cleaning_tax')->default(false)->after('cleaning_paid');
            $table->boolean('linen_tax')->default(false)->after('linen_paid');
            $table->boolean('parking_tax')->default(false)->after('parking_paid_at');
        });

        // Apply config defaults to existing rows
        $defaults = config('finance.tax_declaration_defaults', [
            'income'   => true,
            'cleaning' => true,
            'linen'    => true,
            'parking'  => false,
        ]);

        DB::table('bookings')->update([
            'income_tax'   => $defaults['income']   ? 1 : 0,
            'cleaning_tax' => $defaults['cleaning']  ? 1 : 0,
            'linen_tax'    => $defaults['linen']     ? 1 : 0,
            'parking_tax'  => $defaults['parking']   ? 1 : 0,
        ]);
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['income_tax', 'cleaning_tax', 'linen_tax', 'parking_tax']);
        });
    }
};
