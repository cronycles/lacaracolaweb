<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('iso2', 2)->nullable()->unique();
            $table->string('name_it', 150);
            $table->string('alloggiati_code', 9)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
