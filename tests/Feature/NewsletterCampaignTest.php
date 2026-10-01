<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\NewsletterMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterDelivery;
use App\Models\NewsletterTemplate;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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

        $this->actingAs($this->admin)->post('/admin/newsletter/templates/'.$template->id.'/send', [
            'person_ids' => [$person->id],
            'manual_emails' => "Anna@example.com\nmanual@example.com",
        ])->assertRedirect('/admin/newsletter');

        $campaign = NewsletterCampaign::firstOrFail();
        $this->assertSame(2, $campaign->total_recipients);
        $this->assertSame(2, NewsletterDelivery::count());
        Mail::assertSent(NewsletterMail::class, 2);
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
}
