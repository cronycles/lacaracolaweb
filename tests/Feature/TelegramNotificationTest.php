<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use App\Services\TelegramBookingMessageBuilder;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_telegram_sends_only_to_users_with_chat_id_and_enabled_flag(): void
    {
        Http::fake();

        User::factory()->create([
            'telegram_chat_id' => 'enabled-chat',
            'telegram_notifications_enabled' => true,
        ]);
        User::factory()->create([
            'telegram_chat_id' => 'disabled-chat',
            'telegram_notifications_enabled' => false,
        ]);
        User::factory()->create([
            'telegram_notifications_enabled' => true,
        ]);

        app(TelegramService::class)->sendToAllRecipients('test message');

        Http::assertSentCount(1);
        Http::assertSent(fn ($request): bool => $request['chat_id'] === 'enabled-chat');
    }

    public function test_user_inherits_role_default_and_resets_when_role_changes(): void
    {
        $enabledRole = Role::create([
            'name' => 'telegram_enabled_role',
            'telegram_notifications_enabled' => true,
        ]);
        $disabledRole = Role::create([
            'name' => 'telegram_disabled_role',
            'telegram_notifications_enabled' => false,
        ]);

        $user = User::factory()->create(['role_id' => $enabledRole->id]);
        $this->assertTrue($user->fresh()->telegram_notifications_enabled);

        $user->update(['role_id' => $disabledRole->id]);
        $this->assertFalse($user->fresh()->telegram_notifications_enabled);

        $user->update(['telegram_notifications_enabled' => true]);
        $this->assertTrue($user->fresh()->telegram_notifications_enabled);
    }

    public function test_guest_message_contains_checkin_data_for_all_booking_guests(): void
    {
        $primary = Person::create([
            'first_name' => 'Anna',
            'last_name' => 'Verdi',
            'gender' => 'F',
            'birth_date' => '1990-01-02',
            'birth_municipality' => 'Genova',
            'birth_province' => 'GE',
            'birth_country_code' => 'IT',
            'nationality_code' => 'IT',
            'document_type' => 'passport',
            'document_number' => 'YA1234567',
            'document_issue_place' => 'Roma',
            'document_issue_country_code' => 'IT',
        ]);
        $companion = Person::create([
            'first_name' => 'Luca',
            'last_name' => 'Bianchi',
        ]);
        $booking = Booking::create([
            'person_id' => $primary->id,
            'checkin' => '2026-08-12',
            'checkout' => '2026-08-16',
            'adults' => 2,
            'checkin_completed_at' => now(),
        ]);
        $booking->additionalGuests()->attach($companion->id);

        $message = app(TelegramBookingMessageBuilder::class)->buildBookingSummary($booking);

        $this->assertStringContainsString('Dati ospiti (check-in online completato)', $message);
        $this->assertStringContainsString('Ospite 1: Anna Verdi', $message);
        $this->assertStringContainsString('Data di nascita: 02/01/1990', $message);
        $this->assertStringContainsString('Numero documento: YA1234567', $message);
        $this->assertStringContainsString('Ospite 2: Luca Bianchi', $message);
        $this->assertStringNotContainsString('Dati eventualmente incompleti', $message);
    }
}
