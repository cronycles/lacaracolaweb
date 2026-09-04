<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\GuestReportingDriverInterface;
use App\Models\Booking;
use App\Models\Country;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\FakeGuestReportingDriver;
use Tests\TestCase;

/**
 * Regression test for the fix described in openspec change `booking-online-checkin`:
 * document fields for guest types 16/17/18 must actually be enforced server-side.
 * Previously, a `nullable` rule silently skipped the Closure that checked this,
 * so an admin could save (and even send) a guest missing required document data.
 */
class GuestReportingValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private int $driverCallCount = 0;

    private array $driverGuests = [];

    protected function setUp(): void
    {
        parent::setUp();

        Country::firstOrCreate(['iso2' => 'FR'], ['name_it' => 'Francia']);
        Country::firstOrCreate(['iso2' => 'IT'], ['name_it' => 'Italia']);

        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);

        $superAdminRole = Role::where('name', 'super_admin')->first();
        $this->admin = User::factory()->create(['role_id' => $superAdminRole->id]);

        // Fake driver: counts calls (rejection test asserts it's never reached;
        // success test asserts it's reached exactly once).
        $callCounter = &$this->driverCallCount;
        $driverGuests = &$this->driverGuests;
        $this->app->bind(GuestReportingDriverInterface::class, function () use (&$callCounter, &$driverGuests) {
            return new FakeGuestReportingDriver($callCounter, $driverGuests);
        });
    }

    public function test_saving_a_type_16_guest_without_a_document_is_rejected(): void
    {
        $person = Person::create(['first_name' => 'Anna', 'last_name' => 'Verdi']);
        $booking = Booking::create([
            'person_id' => $person->id,
            'checkin' => now()->addDays(10)->format('Y-m-d'),
            'checkout' => now()->addDays(15)->format('Y-m-d'),
            'adults' => 1,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.guest-reporting.test', $booking), [
                'guests' => [[
                    'person_id' => $person->id,
                    'include' => 1,
                    'tipo_alloggiato' => '16',
                    'gender' => 'M',
                    'birth_date' => '1990-01-01',
                    'nationality_code' => 'FR',
                    'birth_country_code' => 'FR',
                    'birth_municipality' => 'Paris',
                    'document_type' => '',
                    'document_number' => '',
                    'document_issue_country_code' => '',
                    'document_issue_place' => '',
                ]],
            ])
            ->assertSessionHasErrors([
                'guests.0.document_type',
                'guests.0.document_number',
                'guests.0.document_issue_country_code',
            ]);

        $person->refresh();
        $this->assertNull($person->document_type);
        $this->assertSame(0, $this->driverCallCount, 'Driver must not be called when validation fails.');
    }

    public function test_italian_birth_requires_birth_province(): void
    {
        $person = Person::create(['first_name' => 'Anna', 'last_name' => 'Verdi']);
        $booking = Booking::create([
            'person_id' => $person->id,
            'checkin' => now()->addDays(10)->format('Y-m-d'),
            'checkout' => now()->addDays(15)->format('Y-m-d'),
            'adults' => 1,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.guest-reporting.test', $booking), [
                'guests' => [[
                    'person_id' => $person->id,
                    'include' => 1,
                    'tipo_alloggiato' => '16',
                    'gender' => 'M',
                    'birth_date' => '1990-01-01',
                    'nationality_code' => 'IT',
                    'birth_country_code' => 'IT',
                    'birth_municipality' => 'Genova',
                    'birth_province' => '',
                    'document_type' => 'passport',
                    'document_number' => 'X1234567',
                    'document_issue_country_code' => 'IT',
                    'document_issue_place' => 'Genova',
                ]],
            ])
            ->assertSessionHasErrors(['guests.0.birth_province']);

        $this->assertSame(0, $this->driverCallCount, 'Driver must not be called when validation fails.');
    }

    public function test_saving_a_type_16_guest_with_full_document_data_succeeds(): void
    {
        $person = Person::create(['first_name' => 'Anna', 'last_name' => 'Verdi']);
        $booking = Booking::create([
            'person_id' => $person->id,
            'checkin' => now()->addDays(10)->format('Y-m-d'),
            'checkout' => now()->addDays(15)->format('Y-m-d'),
            'adults' => 1,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.guest-reporting.test', $booking), [
                'guests' => [[
                    'person_id' => $person->id,
                    'include' => 1,
                    'tipo_alloggiato' => '16',
                    'gender' => 'M',
                    'birth_date' => '1990-01-01',
                    'nationality_code' => 'FR',
                    'birth_country_code' => 'FR',
                    'birth_municipality' => 'Paris',
                    'document_type' => 'passport',
                    'document_number' => 'X1234567',
                    'document_issue_country_code' => 'FR',
                    'document_issue_place' => '',
                ]],
            ])
            ->assertRedirect(route('admin.guest-reporting.show', $booking))
            ->assertSessionHas('success');

        $person->refresh();
        $this->assertSame('passport', $person->document_type);
        $this->assertSame(1, $this->driverCallCount, 'Driver must be called exactly once when validation passes.');
    }

    public function test_simulated_date_test_uses_today_and_three_nights(): void
    {
        $person = Person::create(['first_name' => 'Anna', 'last_name' => 'Verdi']);
        $booking = Booking::create([
            'person_id' => $person->id,
            'checkin' => now()->addDays(14)->format('Y-m-d'),
            'checkout' => now()->addDays(18)->format('Y-m-d'),
            'adults' => 1,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.guest-reporting.test-simulated-dates', $booking), [
                'guests' => [[
                    'person_id' => $person->id,
                    'include' => 1,
                    'tipo_alloggiato' => '16',
                    'gender' => 'M',
                    'birth_date' => '1990-01-01',
                    'nationality_code' => 'FR',
                    'birth_country_code' => 'FR',
                    'birth_municipality' => 'Paris',
                    'document_type' => 'passport',
                    'document_number' => 'X1234567',
                    'document_issue_country_code' => 'FR',
                    'document_issue_place' => '',
                ]],
            ])
            ->assertRedirect(route('admin.guest-reporting.show', $booking));

        $guest = $this->driverGuests[0];
        $this->assertSame(today()->format('d/m/Y'), $guest->arrivalDate);
        $this->assertSame(3, $guest->stayNights);
        $this->assertSame($booking->checkin->format('d/m/Y'), $booking->fresh()->checkin->format('d/m/Y'));
        $this->assertSame('test_simulated', $booking->guestReports()->latest('id')->value('mode'));
    }

    public function test_single_included_guest_is_promoted_to_single_guest(): void
    {
        $excluded = Person::create(['first_name' => 'Anna', 'last_name' => 'Verdi']);
        $included = Person::create(['first_name' => 'Luca', 'last_name' => 'Bianchi']);
        $booking = Booking::create([
            'person_id' => $excluded->id,
            'checkin' => now()->addDays(10)->format('Y-m-d'),
            'checkout' => now()->addDays(15)->format('Y-m-d'),
            'adults' => 2,
        ]);
        $booking->additionalGuests()->attach($included->id);

        $this->actingAs($this->admin)
            ->post(route('admin.guest-reporting.test', $booking), [
                'guests' => [
                    [
                        'person_id' => $excluded->id,
                        'include' => 0,
                        'tipo_alloggiato' => '18',
                    ],
                    [
                        'person_id' => $included->id,
                        'include' => 1,
                        'tipo_alloggiato' => '20',
                        'gender' => 'M',
                        'birth_date' => '1990-01-01',
                        'nationality_code' => 'FR',
                        'birth_country_code' => 'FR',
                        'birth_municipality' => 'Paris',
                        'document_type' => 'passport',
                        'document_number' => 'X1234567',
                        'document_issue_country_code' => 'FR',
                        'document_issue_place' => '',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.guest-reporting.show', $booking))
            ->assertSessionHas('success');

        $report = $booking->guestReports()->latest('id')->first();
        $this->assertSame('16', $report->guests_payload[0]['tipoAlloggiato']);
    }
}
