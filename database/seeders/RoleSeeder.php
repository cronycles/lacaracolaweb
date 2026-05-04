<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissions = Permission::all();

        // super_admin: all permissions
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['description' => 'Accesso completo a tutte le funzionalità'],
        );
        $superAdmin->permissions()->sync($allPermissions->pluck('id'));

        // host_keeper: viewer only (calendar, bookings, people — read-only)
        $hostKeeper = Role::firstOrCreate(
            ['name' => 'host_keeper'],
            ['description' => 'Accesso in sola lettura a calendario, prenotazioni e ospiti'],
        );
        $hostKeeperPermissions = $allPermissions->whereIn('name', [
            'view_bookings',
            'view_people',
            'view_calendar',
        ]);
        $hostKeeper->permissions()->sync($hostKeeperPermissions->pluck('id'));

        // Assign super_admin role to the owner account
        $owner = User::where('email', 'cronycles@gmail.com')->first();
        if ($owner) {
            $owner->role_id = $superAdmin->id;
            $owner->save();
        }
    }
}
