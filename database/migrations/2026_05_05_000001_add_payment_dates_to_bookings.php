<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Data di imputazione dell'incasso ricevuto (default: checkout)
            $table->date('income_paid_at')->nullable()->after('income_paid');
            // Data di imputazione di pulizie + biancheria (default: checkout)
            $table->date('services_paid_at')->nullable()->after('linen_paid');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['income_paid_at', 'services_paid_at']);
        });
    }
};
