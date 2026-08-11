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
        Schema::table('roles', function (Blueprint $table): void {
            $table->boolean('telegram_notifications_enabled')
                ->default(false)
                ->after('description');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('telegram_notifications_enabled')
                ->default(false)
                ->after('telegram_chat_id');
        });

        DB::table('roles')
            ->whereIn('name', ['super_admin', 'host_owner', 'host_keeper'])
            ->update(['telegram_notifications_enabled' => true]);

        DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->whereIn('roles.name', ['super_admin', 'host_owner', 'host_keeper'])
            ->update(['users.telegram_notifications_enabled' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('telegram_notifications_enabled');
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn('telegram_notifications_enabled');
        });
    }
};
