<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table): void {
            $table->char('gender', 1)->nullable()->after('birth_date');
            $table->string('birth_municipality', 100)->nullable()->after('gender');
            $table->char('birth_province', 2)->nullable()->after('birth_municipality');
            $table->char('birth_country_code', 3)->nullable()->after('birth_province');
            $table->char('nationality_code', 3)->nullable()->after('birth_country_code');
            $table->string('document_issue_place', 100)->nullable()->after('document_number');
            $table->char('document_issue_country_code', 3)->nullable()->after('document_issue_place');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table): void {
            $table->dropColumn([
                'gender',
                'birth_municipality',
                'birth_province',
                'birth_country_code',
                'nationality_code',
                'document_issue_place',
                'document_issue_country_code',
            ]);
        });
    }
};
