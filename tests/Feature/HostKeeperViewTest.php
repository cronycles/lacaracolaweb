<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HostKeeperViewTest extends TestCase
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

    public function test_income_amount_not_in_bookings_index_for_host_keeper(): void
    {
        $this->actingAs($this->hostKeeper)
            ->get('/admin/prenotazioni')
            ->assertStatus(200)
            ->assertDontSee('income_amount')
            ->assertDontSee('Incasso ricevuto');
    }

    public function test_income_amount_not_in_booking_show_for_host_keeper(): void
    {
        $person = Person::create([
            'first_name' => 'Anna',
            'last_name'  => 'Verdi',
        ]);
        $booking = Booking::create([
            'person_id'     => $person->id,
            'checkin'       => now()->addDays(10)->format('Y-m-d'),
            'checkout'      => now()->addDays(15)->format('Y-m-d'),
            'adults'        => 1,
            'income_amount' => 1500.00,
        ]);

        $this->actingAs($this->hostKeeper)
            ->get("/admin/prenotazioni/{$booking->id}")
            ->assertStatus(200)
            ->assertDontSee('Incasso ricevuto')
            ->assertDontSee('1.500,00');
    }
}
