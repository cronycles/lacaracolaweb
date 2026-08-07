<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingConfirmationEmailTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            \Database\Seeders\PermissionSeeder::class,
            \Database\Seeders\RoleSeeder::class,
        ]);

        $superAdminRole = Role::where('name', 'super_admin')->first();
        $this->admin = User::factory()->create(['role_id' => $superAdminRole->id]);
    }

    private function createBooking(array $overrides = []): Booking
    {
        $person = Person::create([
            'first_name' => 'Anna',
            'last_name'  => 'Verdi',
            'email'      => 'anna.verdi@example.com',
        ]);

        return Booking::create(array_merge([
            'person_id' => $person->id,
            'checkin'   => now()->addDays(20)->format('Y-m-d'),
            'checkout'  => now()->addDays(25)->format('Y-m-d'),
            'adults'    => 2,
        ], $overrides));
    }

    public function test_sending_confirmation_email_sets_timestamp_and_bccs_owner(): void
    {
        Mail::fake();

        $booking = $this->createBooking();

        $this->actingAs($this->admin)
            ->post(route('admin.bookings.send-confirmation', $booking))
            ->assertRedirect();

        $booking->refresh();
        $this->assertNotNull($booking->confirmation_sent_at);

        Mail::assertSent(BookingConfirmedMail::class, function (BookingConfirmedMail $mail) use ($booking) {
            return $mail->hasTo('anna.verdi@example.com')
                && $mail->hasBcc(config('apartment.email'))
                && $mail->booking->is($booking);
        });
    }

    public function test_cancellation_deadline_is_omitted_when_checkin_is_too_soon(): void
    {
        Mail::fake();

        $booking = $this->createBooking([
            'checkin'  => now()->addDays(5)->format('Y-m-d'),
            'checkout' => now()->addDays(8)->format('Y-m-d'),
        ]);

        $mail = new BookingConfirmedMail($booking);

        $this->assertFalse($mail->cancellationStillFree);
    }

    public function test_cancellation_deadline_is_present_when_checkin_is_far_enough(): void
    {
        $booking = $this->createBooking([
            'checkin'  => now()->addDays(30)->format('Y-m-d'),
            'checkout' => now()->addDays(35)->format('Y-m-d'),
        ]);

        $mail = new BookingConfirmedMail($booking);

        $this->assertTrue($mail->cancellationStillFree);
    }

    public function test_confirmed_mail_includes_checkin_link_and_generates_token_if_missing(): void
    {
        $booking = $this->createBooking();
        $this->assertNull($booking->checkin_token);

        $mail = new BookingConfirmedMail($booking);

        $booking->refresh();
        $this->assertNotNull($booking->checkin_token);
        $this->assertStringContainsString('/check-in/', $mail->checkinUrl);
        $this->assertStringContainsString($booking->checkin_token, $mail->checkinUrl);
    }

    public function test_rendered_mail_includes_guest_counts_and_total_price_when_present(): void
    {
        $booking = $this->createBooking([
            'adults'          => 2,
            'children'        => 1,
            'babies'          => 1,
            'pets'            => 2,
            'income_amount'   => 500,
            'cleaning_amount' => 50,
            'linen_amount'    => 20,
            'parking_amount'  => 30,
        ]);

        $html = (new BookingConfirmedMail($booking))->render();

        $this->assertStringContainsString('600,00', $html);
        // Adults count (2), children (1), babies (1) and pets (2) all appear as table cell values.
        $this->assertMatchesRegularExpression('/<td>\s*2\s*<\/td>/', $html);
        $this->assertMatchesRegularExpression('/<td>\s*1\s*<\/td>/', $html);
    }

    public function test_rendered_mail_omits_children_babies_pets_rows_and_total_when_zero_or_unknown(): void
    {
        $booking = $this->createBooking([
            'adults'   => 2,
            'children' => 0,
            'babies'   => 0,
            'pets'     => 0,
        ]);

        $html = (new BookingConfirmedMail($booking))->render();

        $this->assertStringNotContainsString(__('app.booking_children'), $html);
        $this->assertStringNotContainsString(__('app.booking_babies'), $html);
        $this->assertStringNotContainsString(__('app.booking_pets'), $html);
        $this->assertStringNotContainsString(__('app.booking_confirmed_mail_summary_total'), $html);
    }
}
