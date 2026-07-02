<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipalities', function (Blueprint $table) {
            $table->id();
            $table->string('code', 9);
            $table->string('name', 150);
            $table->string('province', 2);
            $table->date('expires_at')->nullable();

            $table->index('code');
            $table->index(['name', 'province']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipalities');
    }
};
