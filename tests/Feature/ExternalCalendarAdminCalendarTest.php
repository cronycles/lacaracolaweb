<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AvailabilityBlock;
use App\Models\ExternalCalendarEvent;
use App\Models\ExternalCalendarProvider;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalCalendarAdminCalendarTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        $this->admin = User::factory()->create(['role_id' => Role::query()->where('name', 'host_owner')->value('id')]);
    }

    public function test_calendar_renders_eligible_external_events_with_provider_and_date_details(): void
    {
        $provider = ExternalCalendarProvider::factory()->create([
            'key' => 'airbnb',
            'enabled' => true,
            'last_successful_sync_at' => now(),
        ]);
        ExternalCalendarEvent::factory()->create([
            'external_calendar_provider_id' => $provider->id,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-13',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.calendar', ['month' => '2026-10']))
            ->assertOk()
            ->assertSee('Calendario esterno')
            ->assertSee('Airbnb')
            ->assertSee('10/09/2026')
            ->assertSee('cal-day--external', false);
    }

    public function test_disabled_and_never_synchronized_external_events_are_not_rendered(): void
    {
        $disabled = ExternalCalendarProvider::factory()->create(['key' => 'airbnb', 'enabled' => false]);
        $neverSynced = ExternalCalendarProvider::factory()->create(['key' => 'booking', 'last_successful_sync_at' => null, 'sync_status' => 'never_synced']);
        ExternalCalendarEvent::factory()->create(['external_calendar_provider_id' => $disabled->id, 'start_date' => '2026-09-10', 'end_date' => '2026-09-13']);
        ExternalCalendarEvent::factory()->create(['external_calendar_provider_id' => $neverSynced->id, 'start_date' => '2026-09-15', 'end_date' => '2026-09-18']);

        $this->actingAs($this->admin)
            ->get(route('admin.calendar', ['month' => '2026-10']))
            ->assertOk()
            ->assertDontSee('cal-day--external', false)
            ->assertDontSee('Airbnb')
            ->assertDontSee('Booking.com');
    }

    public function test_external_events_coexist_with_local_manual_blocks_without_mutating_them(): void
    {
        $provider = ExternalCalendarProvider::factory()->create(['key' => 'google_calendar', 'last_successful_sync_at' => now()]);
        ExternalCalendarEvent::factory()->create(['external_calendar_provider_id' => $provider->id, 'start_date' => '2026-09-10', 'end_date' => '2026-09-12']);
        $block = AvailabilityBlock::create(['start_date' => '2026-09-10', 'end_date' => '2026-09-12', 'reason' => 'owner', 'notes' => 'Owner stay']);

        $this->actingAs($this->admin)
            ->get(route('admin.calendar', ['month' => '2026-10']))
            ->assertOk()
            ->assertSee('Google Calendar')
            ->assertSee('Owner stay')
            ->assertSee('cal-day--owner cal-day--external', false);

        $this->assertDatabaseHas('availability_blocks', ['id' => $block->id, 'reason' => 'owner', 'notes' => 'Owner stay']);
    }
}
