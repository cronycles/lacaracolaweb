<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ExternalCalendarProvider;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExternalCalendarSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $hostOwner;

    private User $hostKeeper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $this->hostOwner = User::factory()->create(['role_id' => Role::query()->where('name', 'host_owner')->value('id')]);
        $this->hostKeeper = User::factory()->create(['role_id' => Role::query()->where('name', 'host_keeper')->value('id')]);
    }

    public function test_owner_can_view_fixed_provider_panels_and_persist_configuration(): void
    {
        $this->actingAs($this->hostOwner)
            ->get('/admin/impostazioni')
            ->assertOk()
            ->assertSee('Airbnb')
            ->assertSee('Booking.com')
            ->assertSee('HomeToGo')
            ->assertSee('Google Calendar')
            ->assertSee('Mai sincronizzato');

        $provider = ExternalCalendarProvider::query()->where('key', 'airbnb')->firstOrFail();
        $this->actingAs($this->hostOwner)
            ->put(route('admin.settings.calendar-providers.update', $provider), ['url' => 'https://airbnb.example/calendar.ics', 'enabled' => '1'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('external_calendar_providers', ['id' => $provider->id, 'url' => 'https://airbnb.example/calendar.ics', 'enabled' => true]);
    }

    public function test_owner_cannot_enable_a_provider_without_a_valid_url(): void
    {
        $provider = ExternalCalendarProvider::factory()->create(['key' => 'airbnb', 'url' => null, 'enabled' => false]);

        $this->actingAs($this->hostOwner)
            ->from('/admin/impostazioni')
            ->put(route('admin.settings.calendar-providers.update', $provider), ['enabled' => '1'])
            ->assertRedirect('/admin/impostazioni')
            ->assertSessionHasErrors('url');
    }

    public function test_super_admin_can_manage_external_calendars(): void
    {
        $superAdmin = User::factory()->create(['role_id' => Role::query()->where('name', 'super_admin')->value('id')]);

        $this->actingAs($superAdmin)
            ->get('/admin/impostazioni')
            ->assertOk()
            ->assertSee('Calendari esterni');
    }

    public function test_host_keeper_and_guest_cannot_manage_external_calendars(): void
    {
        $provider = ExternalCalendarProvider::factory()->create(['key' => 'airbnb']);

        $this->get('/admin/impostazioni')->assertRedirect('/admin/login');
        $this->actingAs($this->hostKeeper)->get('/admin/impostazioni')->assertRedirect('/admin/');
        $this->actingAs($this->hostKeeper)->put(route('admin.settings.calendar-providers.update', $provider), ['url' => 'https://airbnb.example/calendar.ics', 'enabled' => '1'])->assertRedirect('/admin/');
        $this->assertDatabaseMissing('external_calendar_providers', ['id' => $provider->id, 'url' => 'https://airbnb.example/calendar.ics']);
    }

    public function test_owner_can_synchronously_sync_one_enabled_provider(): void
    {
        Http::fake(['https://airbnb.example/calendar.ics' => Http::response("BEGIN:VCALENDAR\r\nVERSION:2.0\r\nEND:VCALENDAR\r\n")]);
        $provider = ExternalCalendarProvider::factory()->create(['key' => 'airbnb', 'url' => 'https://airbnb.example/calendar.ics', 'enabled' => true]);

        $this->actingAs($this->hostOwner)
            ->post(route('admin.settings.calendar-providers.sync', $provider))
            ->assertSessionHas('success');

        Http::assertSentCount(1);
        $this->assertDatabaseHas('external_calendar_providers', ['id' => $provider->id, 'sync_status' => 'success', 'imported_event_count' => 0]);
    }
}
