<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds an optional `year` override to pricing_rules.
 *
 * Pricing rules are normally recurring (month/day only, applied every year). Movable
 * holidays (Easter, Pentecost) cannot be expressed that way since their calendar dates
 * shift annually. When `year` is set, the rule only matches that specific year and takes
 * priority over generic recurring rules (see PricingQuoteService). When `year` is null,
 * behaviour is unchanged (recurring every year).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table): void {
            $table->unsignedSmallInteger('year')->nullable()->after('end_day');
            $table->index(['year', 'start_month', 'start_day', 'end_month', 'end_day'], 'pricing_rules_year_period_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table): void {
            $table->dropIndex('pricing_rules_year_period_idx');
            $table->dropColumn('year');
        });
    }
};
