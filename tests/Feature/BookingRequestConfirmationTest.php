<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\BookingRequestDeclinedMail;
use App\Models\AvailabilityBlock;
use App\Models\BookingRequest;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingRequestConfirmationTest extends TestCase
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

    private function makeRequest(array $overrides = []): BookingRequest
    {
        return BookingRequest::create(array_merge([
            'first_name'        => 'Mario',
            'last_name'         => 'Rossi',
            'email'             => 'mario.rossi@example.com',
            'phone'             => '+39 333 1234567',
            'checkin'           => now()->addDays(10)->format('Y-m-d'),
            'checkout'          => now()->addDays(15)->format('Y-m-d'),
            'adults'            => 2,
            'children'          => 0,
            'babies'            => 1,
            'pets'              => 1,
            'message'           => 'Vorremmo portare anche il cane, è possibile?',
            'terms_accepted_at' => now(),
            'locale'            => 'it',
        ], $overrides));
    }

    public function test_pending_queue_lists_only_undeclined_unconfirmed_requests_oldest_first(): void
    {
        $old = $this->makeRequest(['first_name' => 'Oldest', 'last_name' => 'Guest', 'created_at' => now()->subDays(3)]);
        $declined = $this->makeRequest(['first_name' => 'Declined', 'last_name' => 'Guest', 'declined_at' => now()]);
        $confirmed = $this->makeRequest(['first_name' => 'Confirmed', 'last_name' => 'Guest']);
        $recent = $this->makeRequest(['first_name' => 'Recent', 'last_name' => 'Guest', 'created_at' => now()->subDay()]);

        // Simulate an already-confirmed request (linked booking).
        $person = Person::create(['first_name' => 'Confirmed', 'last_name' => 'Guest']);
        \App\Models\Booking::create([
            'person_id'          => $person->id,
            'booking_request_id' => $confirmed->id,
            'checkin'            => $confirmed->checkin,
            'checkout'           => $confirmed->checkout,
            'adults'             => $confirmed->adults,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.booking-requests.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['Oldest Guest', 'Recent Guest']);
        $response->assertDontSee('Declined Guest');
        $response->assertDontSee('Confirmed Guest');
    }

    public function test_confirmed_request_does_not_reappear_after_its_booking_is_deleted(): void
    {
        $request = $this->makeRequest();

        $this->actingAs($this->admin)->post(route('admin.booking-requests.confirm', $request));

        $booking = \App\Models\Booking::where('booking_request_id', $request->id)->first();
        $booking->delete(); // soft delete, e.g. owner cleans up a test/wrong booking

        $response = $this->actingAs($this->admin)->get(route('admin.booking-requests.index'));

        $response->assertDontSee($request->first_name.' '.$request->last_name);
    }

    public function test_confirming_creates_linked_booking_with_empty_financials_and_redirects_to_edit(): void
    {
        $request = $this->makeRequest();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.booking-requests.confirm', $request));

        $booking = \App\Models\Booking::where('booking_request_id', $request->id)->first();

        $this->assertNotNull($booking);
        $response->assertRedirect(route('admin.bookings.edit', $booking));

        $this->assertSame($request->checkin->format('Y-m-d'), $booking->checkin->format('Y-m-d'));
        $this->assertSame($request->checkout->format('Y-m-d'), $booking->checkout->format('Y-m-d'));
        $this->assertSame(2, $booking->adults);
        $this->assertSame(1, $booking->babies);
        $this->assertSame(1, $booking->pets);
        $this->assertSame('direct', $booking->source);
        $this->assertSame('it', $booking->locale);
        $this->assertNull($booking->income_amount);
        $this->assertNull($booking->cleaning_amount);
        $this->assertNull($booking->linen_amount);
        $this->assertNull($booking->parking_amount);
        $this->assertNotNull($booking->availabilityBlock);
        $this->assertSame('booked', $booking->availabilityBlock->reason);
        $this->assertSame($booking->id, $booking->availabilityBlock->booking_id);
        $this->assertNull($booking->availabilityBlock->booking_request_id);
    }

    public function test_confirming_applies_the_same_tax_declaration_defaults_as_creating_a_booking_directly(): void
    {
        config(['finance.tax_declaration_defaults' => [
            'income'   => true,
            'cleaning' => false,
            'linen'    => false,
            'parking'  => false,
        ]]);

        $request = $this->makeRequest();
        AvailabilityBlock::create([
            'start_date'         => $request->checkin,
            'end_date'           => $request->checkout,
            'reason'             => 'pending',
            'booking_request_id' => $request->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.booking-requests.confirm', $request));

        $booking = \App\Models\Booking::where('booking_request_id', $request->id)->first();

        $this->assertTrue($booking->income_tax);
        $this->assertFalse($booking->cleaning_tax);
        $this->assertFalse($booking->linen_tax);
        $this->assertFalse($booking->parking_tax);
    }

    public function test_confirming_matches_an_existing_person_by_email_instead_of_duplicating(): void
    {
        $existing = Person::create([
            'first_name' => 'Mario',
            'last_name'  => 'Rossi',
            'email'      => 'mario.rossi@example.com',
        ]);

        $request = $this->makeRequest();

        $this->actingAs($this->admin)->post(route('admin.booking-requests.confirm', $request));

        $booking = \App\Models\Booking::where('booking_request_id', $request->id)->first();

        $this->assertSame($existing->id, $booking->person_id);
        $this->assertSame(1, Person::count());
    }

    public function test_confirming_enriches_a_matched_persons_missing_contact_info(): void
    {
        $existing = Person::create([
            'first_name' => 'Mario',
            'last_name'  => 'Rossi',
            'email'      => 'mario.rossi@example.com',
            // no phone yet
        ]);

        $request = $this->makeRequest();

        $this->actingAs($this->admin)->post(route('admin.booking-requests.confirm', $request));

        $existing->refresh();
        $this->assertSame('+39 333 1234567', $existing->phone);
    }

    public function test_confirming_without_a_match_creates_a_new_person(): void
    {
        $request = $this->makeRequest();

        $this->actingAs($this->admin)->post(route('admin.booking-requests.confirm', $request));

        $booking = \App\Models\Booking::where('booking_request_id', $request->id)->first();

        $this->assertSame('Mario', $booking->person->first_name);
        $this->assertSame('Rossi', $booking->person->last_name);
    }

    public function test_confirming_prefills_financial_fields_from_the_requests_price_estimate(): void
    {
        $request = $this->makeRequest([
            'estimated_stay_amount'     => 500.00,
            'estimated_cleaning_amount' => 100.00,
            'estimated_linen_amount'    => 50.00,
            'estimated_parking_amount'  => 50.00,
            'estimated_total_amount'    => 700.00,
        ]);

        $this->actingAs($this->admin)->post(route('admin.booking-requests.confirm', $request));

        $booking = \App\Models\Booking::where('booking_request_id', $request->id)->first();

        $this->assertSame('500.00', $booking->income_amount);
        $this->assertSame('100.00', $booking->cleaning_amount);
        $this->assertSame('50.00', $booking->linen_amount);
        $this->assertSame('50.00', $booking->parking_amount);
    }

    public function test_declining_removes_request_from_queue_without_creating_a_booking(): void
    {
        Mail::fake();

        $request = $this->makeRequest();

        $this->actingAs($this->admin)
            ->post(route('admin.booking-requests.decline', $request))
            ->assertRedirect(route('admin.booking-requests.index'));

        $request->refresh();
        $this->assertNotNull($request->declined_at);
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseMissing('availability_blocks', ['booking_request_id' => $request->id]);

        $response = $this->actingAs($this->admin)->get(route('admin.booking-requests.index'));
        $response->assertDontSee('Mario Rossi');
    }

    public function test_declining_sends_a_polite_notification_email_to_the_guest(): void
    {
        Mail::fake();

        $request = $this->makeRequest();

        $this->actingAs($this->admin)->post(route('admin.booking-requests.decline', $request));

        Mail::assertSent(BookingRequestDeclinedMail::class, function (BookingRequestDeclinedMail $mail) use ($request) {
            return $mail->hasTo($request->email) && $mail->bookingRequest->is($request);
        });
    }

    public function test_destroy_permanently_deletes_the_request_without_sending_any_email(): void
    {
        Mail::fake();

        $request = $this->makeRequest();
        AvailabilityBlock::create([
            'start_date'         => $request->checkin,
            'end_date'           => $request->checkout,
            'reason'             => 'pending',
            'booking_request_id' => $request->id,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.booking-requests.destroy', $request))
            ->assertRedirect(route('admin.booking-requests.index'));

        $this->assertDatabaseMissing('booking_requests', ['id' => $request->id]);
        $this->assertDatabaseMissing('availability_blocks', ['booking_request_id' => $request->id]);
        Mail::assertNothingSent();
    }

    public function test_users_without_manage_bookings_permission_cannot_access_or_act_on_the_queue(): void
    {
        $hostKeeperRole = Role::where('name', 'host_keeper')->first();
        $hostKeeper = User::factory()->create(['role_id' => $hostKeeperRole->id]);

        $request = $this->makeRequest();

        $this->actingAs($hostKeeper)->get(route('admin.booking-requests.index'))->assertRedirect('/admin/');
        $this->actingAs($hostKeeper)->post(route('admin.booking-requests.confirm', $request))->assertRedirect('/admin/');
        $this->actingAs($hostKeeper)->post(route('admin.booking-requests.decline', $request))->assertRedirect('/admin/');
        $this->actingAs($hostKeeper)->delete(route('admin.booking-requests.destroy', $request))->assertRedirect('/admin/');

        $this->assertDatabaseCount('bookings', 0);
        $this->assertNull($request->fresh()->declined_at);
    }
}
