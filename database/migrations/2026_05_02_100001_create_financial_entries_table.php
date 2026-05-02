<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_entries', function (Blueprint $table) {
            $table->id();
            // 'income' = ingresso di denaro, 'expense' = uscita
            $table->enum('type', ['income', 'expense']);
            // Free-form category label (e.g. manutenzione, utenze, affitto, altro)
            $table->string('category', 60);
            $table->text('description')->nullable();
            // Amount in EUR (positive value; type determines direction)
            $table->decimal('amount', 8, 2);
            $table->date('entry_date');
            $table->timestamps();

            $table->index('entry_date');
            $table->index(['type', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_entries');
    }
};
