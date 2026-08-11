<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\BookingConfirmedMail;
use App\Mail\BookingHostKeeperMail;
use App\Mail\BookingPaymentReceivedMail;
use App\Mail\CheckinReminderMail;
use App\Models\Booking;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
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
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);

        $superAdminRole = Role::where('name', 'super_admin')->first();
        $this->admin = User::factory()->create(['role_id' => $superAdminRole->id]);
    }

    private function createBooking(array $overrides = []): Booking
    {
        $person = Person::create([
            'first_name' => 'Anna',
            'last_name' => 'Verdi',
            'email' => 'anna.verdi@example.com',
        ]);

        return Booking::create(array_merge([
            'person_id' => $person->id,
            'checkin' => now()->addDays(20)->format('Y-m-d'),
            'checkout' => now()->addDays(25)->format('Y-m-d'),
            'adults' => 2,
        ], $overrides));
    }

    public function test_sending_confirmation_email_sets_timestamp_and_bccs_owner_and_host_keeper(): void
    {
        Mail::fake();

        $hostKeeperRole = Role::where('name', 'host_keeper')->first();
        User::factory()->create([
            'role_id' => $hostKeeperRole->id,
            'email' => 'keeper@example.com',
        ]);

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

        Mail::assertSent(BookingHostKeeperMail::class, function (BookingHostKeeperMail $mail) use ($booking) {
            return $mail->hasTo('keeper@example.com')
                && $mail->hasBcc(config('apartment.email'))
                && $mail->booking->is($booking);
        });
    }

    public function test_admin_can_manually_send_checkin_reminder_email_and_sets_timestamp(): void
    {
        Mail::fake();

        $booking = $this->createBooking();

        $this->actingAs($this->admin)
            ->post(route('admin.bookings.send-checkin-reminder', $booking))
            ->assertRedirect();

        $this->assertNotNull($booking->fresh()->checkin_reminder_sent_at);

        Mail::assertSent(CheckinReminderMail::class, function (CheckinReminderMail $mail) use ($booking) {
            return $mail->hasTo('anna.verdi@example.com')
                && $mail->hasBcc(config('apartment.email'))
                && $mail->booking->is($booking);
        });
    }

    public function test_admin_can_send_payment_received_email_and_marks_payment_received(): void
    {
        Mail::fake();

        $hostKeeperRole = Role::where('name', 'host_keeper')->first();
        User::factory()->create([
            'name' => 'Host Keeper',
            'role_id' => $hostKeeperRole->id,
            'email' => 'keeper@example.com',
            'phone' => '+39 333 1234567',
        ]);

        $booking = $this->createBooking([
            'children' => 1,
            'income_amount' => 500,
            'cleaning_amount' => 50,
            'linen_amount' => 20,
            'parking_amount' => 30,
            'income_paid' => false,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.bookings.send-payment-received', $booking))
            ->assertRedirect();

        $booking->refresh();
        $booking->refresh();
        $this->assertTrue($booking->income_paid);
        $this->assertSame(now()->toDateString(), $booking->income_paid_at->toDateString());
        $this->assertNotNull($booking->payment_received_sent_at);

        $originalPaidAt = $booking->income_paid_at;
        $this->actingAs($this->admin)
            ->post(route('admin.bookings.send-payment-received', $booking))
            ->assertRedirect();

        $this->assertSame($originalPaidAt->toDateString(), $booking->fresh()->income_paid_at->toDateString());

        Mail::assertSent(BookingPaymentReceivedMail::class, function (BookingPaymentReceivedMail $mail) use ($booking) {
            return $mail->hasTo('anna.verdi@example.com')
                && $mail->hasBcc(config('apartment.email'))
                && $mail->booking->is($booking);
        });
    }

    public function test_payment_received_email_contains_checkin_address_maps_and_total(): void
    {
        $hostKeeperRole = Role::where('name', 'host_keeper')->first();
        User::factory()->create([
            'name' => 'Host Keeper',
            'role_id' => $hostKeeperRole->id,
            'email' => 'keeper@example.com',
            'phone' => '+39 333 1234567',
        ]);

        $booking = $this->createBooking([
            'children' => 1,
            'income_amount' => 500,
            'cleaning_amount' => 50,
            'linen_amount' => 20,
            'parking_amount' => 30,
        ]);

        $html = (new BookingPaymentReceivedMail($booking))->render();

        $this->assertStringContainsString('Pagamento ricevuto correttamente', $html);
        $this->assertStringContainsString('600,00', $html);
        $this->assertStringContainsString('Compila il check-in online', $html);
        $this->assertStringContainsString('keeper@example.com', $html);
        $this->assertStringContainsString('+39 333 1234567', $html);
        $this->assertStringContainsString('Via Aurelia 64', $html);
        $this->assertStringContainsString('google.com/maps', $html);
        $this->assertStringContainsString('maps.apple.com', $html);
        $this->assertStringContainsString('openstreetmap.org', $html);
    }

    public function test_manual_checkin_reminder_fails_gracefully_without_guest_email(): void
    {
        Mail::fake();

        $person = Person::create(['first_name' => 'No', 'last_name' => 'Email']);
        $booking = Booking::create([
            'person_id' => $person->id,
            'checkin' => now()->addDays(20)->format('Y-m-d'),
            'checkout' => now()->addDays(25)->format('Y-m-d'),
            'adults' => 1,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.bookings.send-checkin-reminder', $booking))
            ->assertRedirect();

        Mail::assertNothingSent();
    }

    public function test_cancellation_deadline_is_omitted_when_checkin_is_too_soon(): void
    {
        Mail::fake();

        $booking = $this->createBooking([
            'checkin' => now()->addDays(5)->format('Y-m-d'),
            'checkout' => now()->addDays(8)->format('Y-m-d'),
        ]);

        $mail = new BookingConfirmedMail($booking);

        $this->assertFalse($mail->cancellationStillFree);
    }

    public function test_cancellation_deadline_is_present_when_checkin_is_far_enough(): void
    {
        $booking = $this->createBooking([
            'checkin' => now()->addDays(30)->format('Y-m-d'),
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
            'adults' => 2,
            'children' => 1,
            'babies' => 1,
            'pets' => 2,
            'income_amount' => 500,
            'cleaning_amount' => 50,
            'linen_amount' => 20,
            'parking_amount' => 30,
        ]);

        $html = (new BookingConfirmedMail($booking))->render();

        $this->assertStringContainsString('600,00', $html);
        // Adults count (2), children (1), babies (1) and pets (2) all appear as table cell values.
        $this->assertMatchesRegularExpression('/<td>\s*2\s*<\/td>/', $html);
        $this->assertMatchesRegularExpression('/<td>\s*1\s*<\/td>/', $html);
    }

    public function test_host_keeper_mail_contains_operational_costs_without_guest_price(): void
    {
        $booking = $this->createBooking([
            'cleaning_amount' => 50,
            'linen_amount' => 20,
            'parking_amount' => 30,
            'income_amount' => 500,
        ]);
        $booking->person->update([
            'phone' => '3331234567',
            'phone_prefix' => '+39',
        ]);

        $html = (new BookingHostKeeperMail($booking))->render();

        $this->assertStringContainsString('3331234567', $html);
        $this->assertStringContainsString('anna.verdi@example.com', $html);
        $this->assertStringContainsString('Costo totale servizio', $html);
        $this->assertStringContainsString('70,00', $html);
        $this->assertStringContainsString('50,00', $html);
        $this->assertStringContainsString('20,00', $html);
        $this->assertStringContainsString('30,00', $html);
        $this->assertStringNotContainsString('500,00', $html);
    }

    public function test_rendered_mail_omits_children_babies_pets_rows_and_total_when_zero_or_unknown(): void
    {
        $booking = $this->createBooking([
            'adults' => 2,
            'children' => 0,
            'babies' => 0,
            'pets' => 0,
        ]);

        $html = (new BookingConfirmedMail($booking))->render();

        $this->assertStringNotContainsString(__('app.booking_children'), $html);
        $this->assertStringNotContainsString(__('app.booking_babies'), $html);
        $this->assertStringNotContainsString(__('app.booking_pets'), $html);
        $this->assertStringNotContainsString(__('app.booking_confirmed_mail_summary_total'), $html);
    }
}
