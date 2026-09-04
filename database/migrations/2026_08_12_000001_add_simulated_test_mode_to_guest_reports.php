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
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE guest_reports MODIFY mode VARCHAR(20) NOT NULL');

            return;
        }

        Schema::table('guest_reports', function (Blueprint $table): void {
            $table->string('mode', 20)->change();
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE guest_reports MODIFY mode ENUM('test', 'send') NOT NULL");

            return;
        }

        Schema::table('guest_reports', function (Blueprint $table): void {
            $table->enum('mode', ['test', 'send'])->change();
        });
    }
};
