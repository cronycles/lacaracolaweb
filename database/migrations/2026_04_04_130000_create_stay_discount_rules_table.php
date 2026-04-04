<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stay_discount_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('min_nights');
            $table->unsignedSmallInteger('max_nights')->nullable();
            $table->unsignedTinyInteger('discount_percent');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('priority')->default(100);
            $table->timestamps();

            $table->index(['is_active', 'min_nights', 'max_nights']);
            $table->index(['priority', 'min_nights']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stay_discount_rules');
    }
};
