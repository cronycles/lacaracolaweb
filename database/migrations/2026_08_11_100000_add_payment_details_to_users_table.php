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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('tax_code', 16)->nullable()->after('phone');
            $table->string('payment_beneficiary')->nullable()->after('tax_code');
            $table->string('payment_iban', 34)->nullable()->after('payment_beneficiary');
            $table->string('payment_bic', 11)->nullable()->after('payment_iban');
            $table->boolean('payment_enabled')->default(false)->after('payment_bic');
        });

        DB::table('users')
            ->where('name', 'Marco Crosetti')
            ->whereIn('role_id', function ($query): void {
                $query->select('id')
                    ->from('roles')
                    ->where('name', 'host_owner');
            })
            ->update([
                'tax_code'            => 'CRSMRC60D24D969K',
                'payment_beneficiary' => 'Marco Crosetti',
                'payment_iban'        => 'IT81A0301503200000005220710',
                'payment_bic'         => 'FEBIITM2',
                'payment_enabled'    => true,
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'tax_code',
                'payment_beneficiary',
                'payment_iban',
                'payment_bic',
                'payment_enabled',
            ]);
        });
    }
};
