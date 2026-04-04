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
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS pricing_rules_start_date_end_date_index');
        } elseif (Schema::hasColumn('pricing_rules', 'start_date') && Schema::hasColumn('pricing_rules', 'end_date')) {
            Schema::table('pricing_rules', function (Blueprint $table): void {
                $table->dropIndex('pricing_rules_start_date_end_date_index');
            });
        }

        Schema::table('pricing_rules', function (Blueprint $table): void {
            if (Schema::hasColumn('pricing_rules', 'name')) {
                $table->dropColumn('name');
            }

            if (Schema::hasColumn('pricing_rules', 'start_date')) {
                $table->dropColumn('start_date');
            }

            if (Schema::hasColumn('pricing_rules', 'end_date')) {
                $table->dropColumn('end_date');
            }

            if (Schema::hasColumn('pricing_rules', 'min_nights')) {
                $table->dropColumn('min_nights');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table): void {
            if (! Schema::hasColumn('pricing_rules', 'name')) {
                $table->string('name', 80)->default('Regola prezzo');
            }

            if (! Schema::hasColumn('pricing_rules', 'start_date')) {
                $table->date('start_date')->default('2000-01-01');
            }

            if (! Schema::hasColumn('pricing_rules', 'end_date')) {
                $table->date('end_date')->default('2000-01-01');
            }

            if (! Schema::hasColumn('pricing_rules', 'min_nights')) {
                $table->unsignedTinyInteger('min_nights')->default(3);
            }

            $table->index(['start_date', 'end_date'], 'pricing_rules_start_date_end_date_index');
        });
    }
};
