<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            // Price quote shown to the guest on the public form, recalculated
            // server-side at submission time (never trust client input) so the
            // owner can see it in the requests queue and have it pre-filled
            // into the booking's financial fields on acceptance.
            $table->decimal('estimated_stay_amount', 8, 2)->nullable()->after('declined_at');
            $table->decimal('estimated_cleaning_amount', 8, 2)->nullable()->after('estimated_stay_amount');
            $table->decimal('estimated_linen_amount', 8, 2)->nullable()->after('estimated_cleaning_amount');
            $table->decimal('estimated_total_amount', 8, 2)->nullable()->after('estimated_linen_amount');
        });
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->dropColumn([
                'estimated_stay_amount',
                'estimated_cleaning_amount',
                'estimated_linen_amount',
                'estimated_total_amount',
            ]);
        });
    }
};
