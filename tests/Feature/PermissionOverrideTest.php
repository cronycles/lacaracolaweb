<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionOverrideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);
    }

    public function test_host_keeper_with_manage_bookings_override_can_access_create_booking(): void
    {
        $hostKeeperRole = Role::where('name', 'host_keeper')->first();
        $hostKeeper = User::factory()->create(['role_id' => $hostKeeperRole->id]);
        $manageBookingsPerm = Permission::where('name', 'manage_bookings')->first();

        // Grant per-user override
        $hostKeeper->permissionOverrides()->attach($manageBookingsPerm->id);
        $hostKeeper->load('permissionOverrides');

        $this->actingAs($hostKeeper)
            ->get('/admin/prenotazioni/create')
            ->assertStatus(200);
    }

    public function test_manage_users_override_is_non_delegable(): void
    {
        $hostKeeperRole = Role::where('name', 'host_keeper')->first();
        $hostKeeper = User::factory()->create(['role_id' => $hostKeeperRole->id]);
        $manageUsersPerm = Permission::where('name', 'manage_users')->first();

        // Even if somehow attached, hasPermission should return false
        $hostKeeper->permissionOverrides()->attach($manageUsersPerm->id);
        $hostKeeper->load('role.permissions', 'permissionOverrides');

        $this->assertFalse($hostKeeper->hasPermission('manage_users'));

        $this->actingAs($hostKeeper)
            ->get('/admin/utenti')
            ->assertRedirect('/admin/');
    }
}
