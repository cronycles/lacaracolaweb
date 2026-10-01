<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\NewsletterMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterDelivery;
use App\Models\NewsletterSuppression;
use App\Models\NewsletterTemplate;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class NewsletterCampaignTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        $role = Role::where('name', 'super_admin')->firstOrFail();
        $this->admin = User::factory()->create(['role_id' => $role->id]);
    }

    public function test_admin_can_create_and_preview_a_bilingual_template(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/newsletter/templates', [
            'title' => 'Estate 2026',
            'subject' => 'Novita',
            'content_it' => json_encode([['type' => 'heading', 'level' => 1, 'text' => 'Ciao']]),
            'content_en' => json_encode([['type' => 'paragraph', 'text' => 'Hello']]),
        ]);

        $template = NewsletterTemplate::firstOrFail();
        $response->assertRedirect('/admin/newsletter');
        $this->actingAs($this->admin)
            ->get('/admin/newsletter/templates/'.$template->id.'/anteprima')
            ->assertOk()
            ->assertSee('Ciao')
            ->assertSee('Hello')
            ->assertSee('/it')
            ->assertSee('/en');
    }

    public function test_stale_get_confirmation_url_redirects_to_newsletter(): void
    {
        $template = NewsletterTemplate::create([
            'created_by' => $this->admin->id,
            'title' => 'Test',
            'subject' => 'Test subject',
            'content_it' => [],
            'content_en' => [],
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/newsletter/templates/'.$template->id.'/send/confirm')
            ->assertRedirect('/admin/newsletter');
    }

    public function test_uploaded_image_is_saved_in_the_template_document(): void
    {
        Storage::fake('public');
        $upload = $this->actingAs($this->admin)->postJson('/admin/newsletter/images', [
            'image' => UploadedFile::fake()->image('newsletter.jpg'),
        ]);
        $upload->assertOk()->assertJsonStructure(['path', 'url']);
        $path = $upload->json('path');
        Storage::disk('public')->assertExists($path);

        $template = $this->actingAs($this->admin)->post('/admin/newsletter/templates', [
            'title' => 'Immagine',
            'subject' => 'Immagine',
            'content_it' => json_encode([['type' => 'image', 'path' => $path, 'alt' => 'Casa']]),
            'content_en' => '[]',
        ])->assertRedirect('/admin/newsletter');

        $this->assertSame($path, NewsletterTemplate::firstOrFail()->content_it[0]['path']);
    }

    public function test_send_creates_one_delivery_per_unique_eligible_recipient(): void
    {
        Mail::fake();
        $template = NewsletterTemplate::create([
            'created_by' => $this->admin->id,
            'title' => 'Test',
            'subject' => 'Test subject',
            'content_it' => [],
            'content_en' => [],
        ]);
        $person = Person::create(['first_name' => 'Anna', 'last_name' => 'Verdi', 'email' => 'Anna@example.com', 'newsletter_subscribed' => true]);
        Person::create(['first_name' => 'No', 'last_name' => 'Email', 'newsletter_subscribed' => true]);

        $payload = [
            'person_ids' => [$person->id],
            'manual_emails' => "Anna@example.com\nmanual@example.com",
        ];
        $this->actingAs($this->admin)->post('/admin/newsletter/templates/'.$template->id.'/send/confirm', $payload)
            ->assertOk()
            ->assertSee('2')
            ->assertSee('manual@example.com');
        $this->actingAs($this->admin)->post('/admin/newsletter/templates/'.$template->id.'/send', $payload)
            ->assertRedirect('/admin/newsletter');

        $campaign = NewsletterCampaign::firstOrFail();
        $this->assertSame(2, $campaign->total_recipients);
        $this->assertSame(2, NewsletterDelivery::count());
        Mail::assertSent(NewsletterMail::class, 2);
    }

    public function test_test_send_does_not_create_a_campaign(): void
    {
        Mail::fake();
        $template = NewsletterTemplate::create([
            'created_by' => $this->admin->id,
            'title' => 'Test',
            'subject' => 'Test subject',
            'content_it' => [],
            'content_en' => [],
        ]);

        $this->actingAs($this->admin)->post('/admin/newsletter/templates/'.$template->id.'/test')->assertRedirect();

        $this->assertDatabaseCount('newsletter_campaigns', 0);
        $this->assertDatabaseCount('newsletter_deliveries', 0);
        Mail::assertSent(NewsletterMail::class, 1);
    }

    public function test_mail_rendering_contains_both_languages_ctas_and_unsubscribe_link(): void
    {
        $campaign = NewsletterCampaign::create([
            'title' => 'Test',
            'subject' => 'Oggetto condiviso',
            'content_it' => [['type' => 'paragraph', 'text' => 'Contenuto italiano']],
            'content_en' => [['type' => 'paragraph', 'text' => 'English content']],
        ]);

        $html = (new NewsletterMail($campaign, 'reader@example.com'))->render();

        $this->assertStringContainsString('Contenuto italiano', $html);
        $this->assertStringContainsString('English content', $html);
        $this->assertStringContainsString(route('it.home'), $html);
        $this->assertStringContainsString(route('en.home'), $html);
        $this->assertStringContainsString('/newsletter/unsubscribe', $html);
        $this->assertStringNotContainsString('other-recipient@example.com', $html);
    }

    public function test_unsubscribe_can_be_reactivated_by_admin(): void
    {
        $person = Person::create(['first_name' => 'Anna', 'last_name' => 'Verdi', 'email' => 'anna@example.com', 'newsletter_subscribed' => true]);
        $url = URL::signedRoute('newsletter.unsubscribe', ['email' => $person->email]);

        $this->get($url)->assertOk()->assertSee('Non riceverai più');
        $this->assertDatabaseHas('newsletter_suppressions', ['email' => 'anna@example.com']);
        $this->assertFalse((bool) $person->fresh()->newsletter_subscribed);

        $this->actingAs($this->admin)->post('/admin/newsletter/suppressions/reactivate', ['email' => $person->email])->assertRedirect();
        $this->assertDatabaseMissing('newsletter_suppressions', ['email' => 'anna@example.com']);
        $this->assertTrue((bool) $person->fresh()->newsletter_subscribed);
    }

    public function test_invalid_unsubscribe_signature_does_not_change_subscription_and_valid_link_is_idempotent(): void
    {
        $person = Person::create(['first_name' => 'Anna', 'last_name' => 'Verdi', 'email' => 'anna@example.com', 'newsletter_subscribed' => true]);
        $this->get('/newsletter/unsubscribe?email=anna%40example.com&signature=invalid')->assertForbidden();
        $this->assertTrue((bool) $person->fresh()->newsletter_subscribed);

        $url = URL::signedRoute('newsletter.unsubscribe', ['email' => $person->email]);
        $this->get($url)->assertOk();
        $this->get($url)->assertOk();
        $this->assertFalse((bool) $person->fresh()->newsletter_subscribed);
        $this->assertDatabaseCount('newsletter_suppressions', 1);
    }

    public function test_retry_does_not_send_to_a_suppressed_delivery(): void
    {
        Mail::fake();
        $campaign = NewsletterCampaign::create([
            'title' => 'Test', 'subject' => 'Test', 'content_it' => [], 'content_en' => [], 'status' => 'completed_with_errors', 'total_recipients' => 1,
        ]);
        $delivery = NewsletterDelivery::create([
            'newsletter_campaign_id' => $campaign->id, 'email' => 'manual@example.com', 'status' => 'failed', 'error' => 'temporary',
        ]);
        NewsletterSuppression::create(['email' => 'manual@example.com', 'suppressed_at' => now()]);

        $this->actingAs($this->admin)->post('/admin/newsletter/campaigns/'.$campaign->id.'/retry')->assertRedirect();

        $this->assertSame('failed', $delivery->fresh()->status);
        $this->assertStringContainsString('disiscritto', $delivery->fresh()->error);
        Mail::assertNothingSent();
    }
}
