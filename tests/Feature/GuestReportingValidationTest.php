<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\GuestReportingDriverInterface;
use App\Models\Booking;
use App\Models\Country;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use App\Services\GuestReporting\SubmissionResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    protected function setUp(): void
    {
        parent::setUp();

        Country::firstOrCreate(['iso2' => 'FR'], ['name_it' => 'Francia']);
        Country::firstOrCreate(['iso2' => 'IT'], ['name_it' => 'Italia']);

        $this->seed([
            \Database\Seeders\PermissionSeeder::class,
            \Database\Seeders\RoleSeeder::class,
        ]);

        $superAdminRole = Role::where('name', 'super_admin')->first();
        $this->admin = User::factory()->create(['role_id' => $superAdminRole->id]);

        // Fake driver: counts calls (rejection test asserts it's never reached;
        // success test asserts it's reached exactly once).
        $callCounter = &$this->driverCallCount;
        $this->app->bind(GuestReportingDriverInterface::class, function () use (&$callCounter) {
            return new class($callCounter) implements GuestReportingDriverInterface {
                public function __construct(private int &$callCounter) {}

                public function checkConnection(): bool
                {
                    $this->callCounter++;

                    return true;
                }

                public function testDraft(array $guests): SubmissionResult
                {
                    $this->callCounter++;

                    return SubmissionResult::success('OK');
                }

                public function sendGuests(array $guests): SubmissionResult
                {
                    $this->callCounter++;

                    return SubmissionResult::success('OK');
                }
            };
        });
    }

    public function test_saving_a_type_16_guest_without_a_document_is_rejected(): void
    {
        $person = Person::create(['first_name' => 'Anna', 'last_name' => 'Verdi']);
        $booking = Booking::create([
            'person_id' => $person->id,
            'checkin'   => now()->addDays(10)->format('Y-m-d'),
            'checkout'  => now()->addDays(15)->format('Y-m-d'),
            'adults'    => 1,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.guest-reporting.test', $booking), [
                'guests' => [[
                    'person_id'                   => $person->id,
                    'include'                     => 1,
                    'tipo_alloggiato'              => '16',
                    'gender'                       => 'M',
                    'birth_date'                   => '1990-01-01',
                    'nationality_code'             => 'FR',
                    'birth_country_code'           => 'FR',
                    'birth_municipality'           => 'Paris',
                    'document_type'                => '',
                    'document_number'               => '',
                    'document_issue_country_code'  => '',
                    'document_issue_place'          => '',
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
            'checkin'   => now()->addDays(10)->format('Y-m-d'),
            'checkout'  => now()->addDays(15)->format('Y-m-d'),
            'adults'    => 1,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.guest-reporting.test', $booking), [
                'guests' => [[
                    'person_id'                   => $person->id,
                    'include'                     => 1,
                    'tipo_alloggiato'              => '16',
                    'gender'                       => 'M',
                    'birth_date'                   => '1990-01-01',
                    'nationality_code'             => 'IT',
                    'birth_country_code'           => 'IT',
                    'birth_municipality'           => 'Genova',
                    'birth_province'               => '',
                    'document_type'                => 'passport',
                    'document_number'              => 'X1234567',
                    'document_issue_country_code'  => 'IT',
                    'document_issue_place'         => 'Genova',
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
            'checkin'   => now()->addDays(10)->format('Y-m-d'),
            'checkout'  => now()->addDays(15)->format('Y-m-d'),
            'adults'    => 1,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.guest-reporting.test', $booking), [
                'guests' => [[
                    'person_id'                   => $person->id,
                    'include'                     => 1,
                    'tipo_alloggiato'              => '16',
                    'gender'                       => 'M',
                    'birth_date'                   => '1990-01-01',
                    'nationality_code'             => 'FR',
                    'birth_country_code'           => 'FR',
                    'birth_municipality'           => 'Paris',
                    'document_type'                => 'passport',
                    'document_number'               => 'X1234567',
                    'document_issue_country_code'  => 'FR',
                    'document_issue_place'          => '',
                ]],
            ])
            ->assertRedirect(route('admin.guest-reporting.show', $booking))
            ->assertSessionHas('success');

        $person->refresh();
        $this->assertSame('passport', $person->document_type);
        $this->assertSame(1, $this->driverCallCount, 'Driver must be called exactly once when validation passes.');
    }
}
