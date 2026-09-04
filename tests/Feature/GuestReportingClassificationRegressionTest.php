<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\GuestTypesSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test: the admin guest-reporting form's `tipo_alloggiato` defaults
 * must stay unchanged after extracting the classification logic into
 * `App\Services\GuestReporting\GuestClassifier` (see openspec change
 * `booking-online-checkin`, group 2).
 */
class GuestReportingClassificationRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);
        $this->seed(GuestTypesSeeder::class);

        $superAdminRole = Role::where('name', 'super_admin')->first();
        $this->admin = User::factory()->create(['role_id' => $superAdminRole->id]);
    }

    private function createBookingWithGuests(int $totalGuests): Booking
    {
        $primary = Person::create(['first_name' => 'Anna', 'last_name' => 'Verdi']);

        $booking = Booking::create([
            'person_id' => $primary->id,
            'checkin' => now()->addDays(10)->format('Y-m-d'),
            'checkout' => now()->addDays(15)->format('Y-m-d'),
            'adults' => $totalGuests,
        ]);

        for ($i = 1; $i < $totalGuests; $i++) {
            $companion = Person::create(['first_name' => "Companion{$i}", 'last_name' => 'Test']);
            $booking->additionalGuests()->attach($companion->id);
        }

        return $booking;
    }

    public function test_single_guest_booking_defaults_to_type_16(): void
    {
        $booking = $this->createBookingWithGuests(1);

        $response = $this->actingAs($this->admin)->get(route('admin.guest-reporting.show', $booking));

        $response->assertOk();
        $response->assertSee('name="guests[0][tipo_alloggiato]" value="16"', false);
    }

    public function test_multi_guest_booking_defaults_to_18_and_20(): void
    {
        $booking = $this->createBookingWithGuests(3);

        $response = $this->actingAs($this->admin)->get(route('admin.guest-reporting.show', $booking));

        $response->assertOk();
        $response->assertSee('name="guests[0][tipo_alloggiato]" value="18"', false);
        $response->assertSee('name="guests[1][tipo_alloggiato]" value="20"', false);
        $response->assertSee('name="guests[2][tipo_alloggiato]" value="20"', false);
    }
}
