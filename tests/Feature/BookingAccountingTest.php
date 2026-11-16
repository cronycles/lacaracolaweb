<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\BookingConfirmedMail;
use App\Mail\BookingHostKeeperMail;
use App\Mail\BookingPaymentReceivedMail;
use App\Models\Booking;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingAccountingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Person $person;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 6, 15));
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        $this->admin = User::factory()->create(['role_id' => Role::where('name', 'super_admin')->firstOrFail()->id]);
        $this->person = Person::create(['first_name' => 'Test', 'last_name' => 'Guest', 'email' => 'guest@example.com']);
    }

    private function booking(array $attributes = []): Booking
    {
        return Booking::create(array_merge([
            'person_id' => $this->person->id,
            'checkin' => '2026-07-01',
            'checkout' => '2026-07-05',
            'adults' => 1,
            'source' => 'direct',
        ], $attributes))->fresh();
    }

    private function form(array $attributes = []): array
    {
        return array_merge([
            'person_id' => $this->person->id,
            'checkin' => '2026-07-01',
            'checkout' => '2026-07-05',
            'adults' => 1,
            'source' => 'direct',
            'checkin_reminder_enabled' => 1,
            'income_amount' => 1000,
            'cleaning_amount' => 100,
            'linen_amount' => 50,
            'parking_amount' => 80,
            'income_paid' => 1,
            'income_paid_at' => '2026-01-10',
            'cleaning_paid' => 0,
            'linen_paid' => 0,
            'parking_paid' => 0,
            'income_includes_cleaning' => 1,
            'income_includes_linen' => 1,
            'income_includes_parking' => 1,
        ], $attributes);
    }

    public function test_accounting_dashboard_and_tax_use_the_same_dated_movements_across_years(): void
    {
        $this->booking([
            'income_amount' => 1000, 'income_paid' => true, 'income_paid_at' => '2026-01-10',
            'cleaning_amount' => 100, 'cleaning_paid' => true, 'services_paid_at' => '2026-02-10',
            'linen_amount' => 50, 'linen_paid' => true,
            'parking_amount' => 80, 'income_includes_parking' => true,
            'income_tax' => true, 'cleaning_tax' => true, 'linen_tax' => false, 'parking_tax' => true,
        ]);
        $this->booking([
            'parking_amount' => 30, 'parking_paid' => true, 'parking_paid_at' => '2025-12-10', 'parking_tax' => true,
            'income_amount' => 900, 'income_paid' => true, 'income_paid_at' => null,
            'cleaning_amount' => 70, 'cleaning_paid' => true, 'services_paid_at' => null,
        ]);
        $this->booking([
            'income_amount' => 9999, 'income_paid' => true, 'income_paid_at' => '2026-01-01',
            'canceled_at' => now(),
        ]);

        $finance = $this->actingAs($this->admin)->get(route('admin.finance.index', ['year' => 2026]));
        $finance->assertOk()->assertViewHas('totals', fn ($t) => $t['income'] == 1000 && $t['expenses'] == 150 && $t['balance'] == 850)
            ->assertViewHas('previousBalance', fn ($b) => $b == 30)
            ->assertViewHas('globalBalance', fn ($b) => $b == 880)
            ->assertViewHas('availableYears', fn ($years) => in_array(2025, $years, true))
            ->assertViewHas('monthlyData', fn ($m) => $m[1]['income'] == 1000 && $m[2]['expenses'] == 150)
            ->assertViewHas('movements', fn ($m) => $m->count() === 3 && $m->where('type', 'income')->count() === 1);
        $this->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('stats', fn ($s) => $s['total_income'] == 1000 && $s['total_expenses'] == 150 && $s['global_balance'] == 880);
        $this->get(route('admin.tax-declaration.index', ['year' => 2026]))->assertOk()
            ->assertViewHas('totals', fn ($t) => $t['income'] == 1000 && $t['expenses'] == 100)
            ->assertViewHas('movements', fn ($m) => $m->count() === 2);
        $this->get(route('admin.tax-declaration.index', ['year' => 2025]))->assertOk()
            ->assertViewHas('totals', fn ($t) => $t['income'] == 30 && $t['expenses'] == 0);
    }

    public function test_form_validates_dates_and_included_amounts_and_synchronizes_parking(): void
    {
        $booking = $this->booking();
        $this->actingAs($this->admin)->put(route('admin.bookings.update', $booking), $this->form([
            'income_paid_at' => null, 'cleaning_paid' => 1,
        ]))->assertSessionHasErrors(['income_paid_at', 'services_paid_at']);
        $this->put(route('admin.bookings.update', $booking), $this->form([
            'income_amount' => 200,
        ]))->assertSessionHasErrors('income_amount');
        $this->put(route('admin.bookings.update', $booking), $this->form([
            'income_amount' => null,
        ]))->assertSessionHasErrors('income_amount');

        $this->put(route('admin.bookings.update', $booking), $this->form())->assertSessionHasNoErrors();
        $booking->refresh();
        $this->assertTrue($booking->parking_paid);
        $this->assertSame('2026-01-10', $booking->parking_paid_at->toDateString());
        $this->assertSame(850.0, $booking->indicative_net);

        $this->put(route('admin.bookings.update', $booking), $this->form([
            'income_includes_parking' => 0,
            'parking_paid' => 1,
            'parking_paid_at' => '2026-01-10',
            'income_includes_cleaning' => 0,
            'cleaning_paid' => 1,
            'services_paid_at' => '2026-02-10',
        ]))->assertSessionHasNoErrors();
        $this->assertSame(930.0, $booking->fresh()->indicative_net);
    }

    public function test_empty_and_zero_included_amounts_are_accepted(): void
    {
        $booking = $this->booking();
        $this->actingAs($this->admin)->put(route('admin.bookings.update', $booking), $this->form([
            'income_amount' => 0,
            'income_paid' => 0,
            'income_paid_at' => null,
            'cleaning_amount' => null,
            'linen_amount' => 0,
            'parking_amount' => null,
        ]))->assertSessionHasNoErrors();
        $this->assertSame(0.0, $booking->fresh()->indicative_net);
    }

    public function test_tax_flags_do_not_create_separate_income_for_included_services(): void
    {
        $this->booking([
            'income_amount' => 1000, 'income_paid' => true, 'income_paid_at' => '2026-01-10',
            'income_tax' => false, 'income_includes_cleaning' => true, 'income_includes_parking' => true,
            'cleaning_amount' => 100, 'cleaning_paid' => true, 'services_paid_at' => '2026-02-10',
            'cleaning_tax' => true, 'parking_amount' => 80, 'parking_tax' => true,
        ]);
        $this->booking([
            'income_amount' => 500, 'income_paid' => false, 'income_paid_at' => '2026-01-10',
            'cleaning_amount' => 30, 'cleaning_paid' => false, 'services_paid_at' => '2026-02-10',
        ]);

        $this->actingAs($this->admin)->get(route('admin.tax-declaration.index', ['year' => 2026]))
            ->assertOk()->assertViewHas('totals', fn ($t) => $t['income'] == 0 && $t['expenses'] == 100)
            ->assertViewHas('movements', fn ($m) => $m->count() === 1 && $m->first()['type'] === 'expense');
    }

    public function test_form_renders_missing_payment_dates_without_filling_them(): void
    {
        $booking = $this->booking(['income_paid' => true, 'income_paid_at' => null]);
        $this->actingAs($this->admin)->get(route('admin.bookings.edit', $booking))
            ->assertOk()
            ->assertSee('Importo prenotazione')
            ->assertSee('Ingresso netto prenotazione')
            ->assertSee('Include posto auto')
            ->assertSee('id="income_paid_at" name="income_paid_at" class="form-input"'.PHP_EOL.'                                   value=""', false);
    }

    public function test_payment_email_synchronizes_included_parking(): void
    {
        Mail::fake();
        $booking = $this->booking(['income_includes_parking' => true, 'income_paid' => false]);
        $this->actingAs($this->admin)->post(route('admin.bookings.send-payment-received', $booking))->assertRedirect();
        $booking->refresh();
        $this->assertTrue($booking->parking_paid);
        $this->assertSame($booking->income_paid_at->toDateString(), $booking->parking_paid_at->toDateString());
    }

    public function test_indicative_net_and_email_parking_states(): void
    {
        $booking = $this->booking(['income_amount' => 100, 'cleaning_amount' => 150, 'parking_amount' => 20]);
        $this->assertSame(-30.0, $booking->indicative_net);
        $this->assertStringContainsString('Da pagare in loco', (new BookingConfirmedMail($booking))->render());
        $booking->update(['parking_paid' => true, 'parking_paid_at' => '2026-03-01']);
        $this->assertStringContainsString('Già pagato', (new BookingPaymentReceivedMail($booking))->render());
        $this->assertStringContainsString('Già pagato', (new BookingHostKeeperMail($booking))->render());
        $booking->update(['income_includes_parking' => true]);
        $this->assertStringContainsString('Già incluso nel prezzo totale', (new BookingConfirmedMail($booking))->render());
        $booking->update(['income_amount' => null]);
        $this->assertNull($booking->indicative_net);
    }

    public function test_migration_initializes_existing_records_and_diagnostic_reports_missing_dates(): void
    {
        $booking = $this->booking(['income_amount' => 1000, 'cleaning_amount' => 100, 'cleaning_paid' => true, 'linen_paid' => false, 'parking_paid' => true]);
        $migration = require database_path('migrations/2026_11_15_180000_add_receipt_inclusions_to_bookings.php');
        $migration->down();
        $migration->up();
        $row = DB::table('bookings')->where('id', $booking->id)->first();
        $this->assertTrue((bool) $row->income_includes_cleaning);
        $this->assertFalse((bool) $row->income_includes_linen);
        $this->assertFalse((bool) $row->income_includes_parking);
        $this->assertNull($row->services_paid_at);
        $this->assertEquals(1000, $row->income_amount);
        $this->assertEquals(100, $row->cleaning_amount);
        $this->artisan('bookings:missing-payment-dates')->expectsOutputToContain('cleaning, parking')->assertSuccessful();

        $new = $this->booking();
        $this->assertFalse($new->income_includes_cleaning);
        $this->assertFalse($new->income_includes_linen);
        $this->assertFalse($new->income_includes_parking);
    }
}
