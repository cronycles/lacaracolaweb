<?php

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table): void {
            $table->unsignedTinyInteger('start_month')->default(1)->after('name');
            $table->unsignedTinyInteger('start_day')->default(1)->after('start_month');
            $table->unsignedTinyInteger('end_month')->default(1)->after('start_day');
            $table->unsignedTinyInteger('end_day')->default(1)->after('end_month');
        });

        DB::table('pricing_rules')->orderBy('id')->chunkById(100, function ($rules): void {
            foreach ($rules as $rule) {
                $start = Carbon::parse($rule->start_date);
                $end = Carbon::parse($rule->end_date);

                DB::table('pricing_rules')
                    ->where('id', $rule->id)
                    ->update([
                        'start_month' => (int) $start->format('m'),
                        'start_day' => (int) $start->format('d'),
                        'end_month' => (int) $end->format('m'),
                        'end_day' => (int) $end->format('d'),
                    ]);
            }
        });

        Schema::table('pricing_rules', function (Blueprint $table): void {
            $table->index(['start_month', 'start_day', 'end_month', 'end_day'], 'pricing_rules_recurring_period_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table): void {
            $table->dropIndex('pricing_rules_recurring_period_idx');
            $table->dropColumn(['start_month', 'start_day', 'end_month', 'end_day']);
        });
    }
};
