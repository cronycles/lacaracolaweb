<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the single `name` field on booking_requests with separate
 * `first_name`/`last_name` fields, so the public availability request form
 * (and the admin queue) never has to guess where the first name ends and
 * the last name begins.
 *
 * Existing rows are backfilled by splitting `name` on the first space
 * (same heuristic previously used ad-hoc in BookingRequestController).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->string('first_name', 100)->nullable()->after('id');
            $table->string('last_name', 100)->nullable()->after('first_name');
        });

        foreach (DB::table('booking_requests')->select('id', 'name')->cursor() as $row) {
            $parts = preg_split('/\s+/', trim((string) $row->name), 2) ?: [(string) $row->name];

            DB::table('booking_requests')
                ->where('id', $row->id)
                ->update([
                    'first_name' => $parts[0] !== '' ? $parts[0] : (string) $row->name,
                    'last_name'  => $parts[1] ?? '',
                ]);
        }

        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->string('first_name', 100)->nullable(false)->change();
            $table->string('last_name', 100)->nullable(false)->change();
        });

        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->string('name', 100)->nullable()->after('id');
        });

        $concat = DB::connection()->getDriverName() === 'mysql'
            ? "TRIM(CONCAT(first_name, ' ', last_name))"
            : "TRIM(first_name || ' ' || last_name)";
        DB::statement("UPDATE booking_requests SET name = {$concat}");

        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->string('name', 100)->nullable(false)->change();
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
