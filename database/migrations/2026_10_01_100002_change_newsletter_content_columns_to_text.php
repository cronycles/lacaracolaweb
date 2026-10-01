<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_templates', function (Blueprint $table): void {
            $table->text('content_it')->change();
            $table->text('content_en')->change();
        });
        Schema::table('newsletter_campaigns', function (Blueprint $table): void {
            $table->text('content_it')->change();
            $table->text('content_en')->change();
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_templates', function (Blueprint $table): void {
            $table->json('content_it')->change();
            $table->json('content_en')->change();
        });
        Schema::table('newsletter_campaigns', function (Blueprint $table): void {
            $table->json('content_it')->change();
            $table->json('content_en')->change();
        });
    }
};
