<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('address_street')->nullable()->after('tax_code');
            $table->string('address_zip', 10)->nullable()->after('address_street');
            $table->string('address_city')->nullable()->after('address_zip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['address_street', 'address_zip', 'address_city']);
        });
    }
};
