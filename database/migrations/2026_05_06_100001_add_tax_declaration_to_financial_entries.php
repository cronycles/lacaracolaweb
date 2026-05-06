<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_entries', function (Blueprint $table) {
            $table->boolean('tax_declaration')->default(false)->after('entry_date');
        });
    }

    public function down(): void
    {
        Schema::table('financial_entries', function (Blueprint $table) {
            $table->dropColumn('tax_declaration');
        });
    }
};
