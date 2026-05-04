<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HostKeeperAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $hostKeeper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            \Database\Seeders\PermissionSeeder::class,
            \Database\Seeders\RoleSeeder::class,
        ]);

        $hostKeeperRole = Role::where('name', 'host_keeper')->first();
        $this->hostKeeper = User::factory()->create(['role_id' => $hostKeeperRole->id]);
    }

    // ── Allowed routes ────────────────────────────────────────────────────────

    public function test_host_keeper_can_access_dashboard(): void
    {
        $this->actingAs($this->hostKeeper)
            ->get('/admin/')
            ->assertStatus(200);
    }

    public function test_host_keeper_can_access_calendar(): void
    {
        $this->actingAs($this->hostKeeper)
            ->get('/admin/calendario')
            ->assertStatus(200);
    }

    public function test_host_keeper_can_access_bookings_index(): void
    {
        $this->actingAs($this->hostKeeper)
            ->get('/admin/prenotazioni')
            ->assertStatus(200);
    }

    public function test_host_keeper_can_access_people_index(): void
    {
        $this->actingAs($this->hostKeeper)
            ->get('/admin/ospiti')
            ->assertStatus(200);
    }

    public function test_host_keeper_can_access_account_security(): void
    {
        $this->actingAs($this->hostKeeper)
            ->get('/admin/impostazioni/sicurezza')
            ->assertStatus(200);
    }

    // ── Forbidden routes (should redirect to dashboard) ───────────────────────

    public function test_host_keeper_cannot_access_pricing(): void
    {
        $this->actingAs($this->hostKeeper)
            ->get('/admin/prezzi')
            ->assertRedirect('/admin/');
    }

    public function test_host_keeper_cannot_access_stay_discounts(): void
    {
        $this->actingAs($this->hostKeeper)
            ->get('/admin/sconti-soggiorno')
            ->assertRedirect('/admin/');
    }

    public function test_host_keeper_cannot_access_accounting(): void
    {
        $this->actingAs($this->hostKeeper)
            ->get('/admin/contabilita')
            ->assertRedirect('/admin/');
    }

    public function test_host_keeper_cannot_access_settings(): void
    {
        $this->actingAs($this->hostKeeper)
            ->get('/admin/impostazioni')
            ->assertRedirect('/admin/');
    }

    public function test_host_keeper_cannot_access_newsletter(): void
    {
        $this->actingAs($this->hostKeeper)
            ->get('/admin/newsletter')
            ->assertRedirect('/admin/');
    }

    public function test_host_keeper_cannot_access_users(): void
    {
        $this->actingAs($this->hostKeeper)
            ->get('/admin/utenti')
            ->assertRedirect('/admin/');
    }

    public function test_host_keeper_cannot_access_pdf_import(): void
    {
        $this->actingAs($this->hostKeeper)
            ->get('/admin/prenotazioni/import-pdf')
            ->assertRedirect('/admin/');
    }

    public function test_host_keeper_cannot_create_booking(): void
    {
        $this->actingAs($this->hostKeeper)
            ->get('/admin/prenotazioni/create')
            ->assertRedirect('/admin/');
    }

    public function test_host_keeper_cannot_create_block(): void
    {
        $this->actingAs($this->hostKeeper)
            ->post('/admin/blocchi', [])
            ->assertRedirect('/admin/');
    }

    public function test_host_keeper_cannot_create_person(): void
    {
        $this->actingAs($this->hostKeeper)
            ->get('/admin/ospiti/create')
            ->assertRedirect('/admin/');
    }
}
