<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);

        $superAdminRole = Role::where('name', 'super_admin')->first();
        $this->superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);
    }

    public function test_super_admin_can_access_dashboard(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/')
            ->assertStatus(200);
    }

    public function test_super_admin_can_access_calendar(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/calendario')
            ->assertStatus(200);
    }

    public function test_super_admin_can_access_bookings(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/prenotazioni')
            ->assertStatus(200);
    }

    public function test_super_admin_can_access_pricing(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/prezzi')
            ->assertStatus(200);
    }

    public function test_super_admin_can_access_accounting(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/contabilita')
            ->assertStatus(200);
    }

    public function test_super_admin_can_access_settings(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/impostazioni')
            ->assertStatus(200);
    }

    public function test_super_admin_can_access_newsletter(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/newsletter')
            ->assertStatus(200);
    }

    public function test_super_admin_can_access_users(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/utenti')
            ->assertStatus(200);
    }

    public function test_super_admin_can_access_people(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/ospiti')
            ->assertStatus(200);
    }
}
