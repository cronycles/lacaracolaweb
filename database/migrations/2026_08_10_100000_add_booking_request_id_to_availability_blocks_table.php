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
        Schema::table('availability_blocks', function (Blueprint $table): void {
            $table->foreignId('booking_request_id')
                ->nullable()
                ->unique()
                ->after('booking_id')
                ->constrained('booking_requests')
                ->cascadeOnDelete();
        });

        $now = now();
        DB::table('booking_requests')
            ->whereNull('declined_at')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('bookings')
                    ->whereColumn('bookings.booking_request_id', 'booking_requests.id');
            })
            ->orderBy('id')
            ->get(['id', 'checkin', 'checkout'])
            ->each(function (object $request) use ($now): void {
                DB::table('availability_blocks')->insert([
                    'start_date'        => $request->checkin,
                    'end_date'          => $request->checkout,
                    'reason'            => 'pending',
                    'booking_request_id' => $request->id,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('availability_blocks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('booking_request_id');
        });
    }
};
