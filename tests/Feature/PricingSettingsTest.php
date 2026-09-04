<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PricingRule;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingSettingsTest extends TestCase
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

    public function test_updating_pricing_settings_persists_percentages_as_decimal_fractions(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.pricing.update'), [
                'pricing_tax_rate' => 22,
                'pricing_tax_gross_up_items' => ['cleaning'],
                'pricing_commission_airbnb' => 16,
                'pricing_commission_booking' => 17,
                'pricing_commission_hometogo' => 16,
                'pricing_weekly_discount_percent' => 12,
                'pricing_monthly_discount_percent' => 22,
                'pricing_cleaning_fee' => 110,
                'pricing_linen_fee_per_person' => 30,
                'pricing_min_nights' => 4,
                'pricing_extra_guest_fee' => 15,
            ])
            ->assertRedirect();

        $this->assertSame('0.22', Setting::get('pricing_tax_rate'));
        $this->assertSame('["cleaning"]', Setting::get('pricing_tax_gross_up_items'));
        $this->assertSame('0.16', Setting::get('pricing_commission_airbnb'));
        $this->assertSame('0.17', Setting::get('pricing_commission_booking'));
        $this->assertSame('0.16', Setting::get('pricing_commission_hometogo'));
        $this->assertSame('0.12', Setting::get('pricing_weekly_discount_percent'));
        $this->assertSame('0.22', Setting::get('pricing_monthly_discount_percent'));
        $this->assertSame('110', Setting::get('pricing_cleaning_fee'));
        $this->assertSame('30', Setting::get('pricing_linen_fee_per_person'));
        $this->assertSame('4', Setting::get('pricing_min_nights'));
        $this->assertSame('15', Setting::get('pricing_extra_guest_fee'));
    }

    public function test_updating_pricing_settings_rejects_out_of_range_values(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.pricing.update'), [
                'pricing_tax_rate' => 150,
                'pricing_commission_airbnb' => 16,
                'pricing_commission_booking' => 17,
                'pricing_commission_hometogo' => 16,
                'pricing_weekly_discount_percent' => 12,
                'pricing_monthly_discount_percent' => 22,
                'pricing_cleaning_fee' => 100,
                'pricing_linen_fee_per_person' => 25,
                'pricing_min_nights' => 3,
                'pricing_extra_guest_fee' => 12,
            ])
            ->assertSessionHasErrors(['pricing_tax_rate']);

        $this->assertNull(Setting::get('pricing_tax_rate'));
    }

    public function test_updating_pricing_settings_rejects_non_positive_values(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.pricing.update'), [
                'pricing_tax_rate' => 21,
                'pricing_commission_airbnb' => 16,
                'pricing_commission_booking' => 17,
                'pricing_commission_hometogo' => 16,
                'pricing_weekly_discount_percent' => 12,
                'pricing_monthly_discount_percent' => 22,
                'pricing_cleaning_fee' => -1,
                'pricing_linen_fee_per_person' => -1,
                'pricing_min_nights' => 0,
                'pricing_extra_guest_fee' => -1,
            ])
            ->assertSessionHasErrors(['pricing_cleaning_fee', 'pricing_linen_fee_per_person', 'pricing_min_nights', 'pricing_extra_guest_fee']);

        $this->assertNull(Setting::get('pricing_cleaning_fee'));
        $this->assertNull(Setting::get('pricing_min_nights'));
    }

    public function test_simulate_response_includes_tax_gross_up_discount_and_portal_fields(): void
    {
        PricingRule::create([
            'start_month' => 1,
            'start_day' => 1,
            'end_month' => 12,
            'end_day' => 31,
            'price_per_night' => 10000,
        ]);

        $checkin = now()->addDays(30)->format('Y-m-d');
        $checkout = now()->addDays(37)->format('Y-m-d'); // 7 nights → qualifies for the weekly discount.

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.pricing.simulate'), [
                'checkin' => $checkin,
                'checkout' => $checkout,
                'guests' => 2,
            ])
            ->assertOk()
            ->assertJson(['available' => true]);

        $response->assertJsonStructure([
            'tax_gross_up_cents',
            'stay_gross_cents',
            'stay_discount_cents',
            'length_discount_rate',
            'portals' => [
                'airbnb' => ['guest_total_cents', 'owner_net_cents', 'commission_rate', 'margin_safe'],
                'booking' => ['guest_total_cents', 'owner_net_cents', 'commission_rate', 'margin_safe'],
                'hometogo' => ['guest_total_cents', 'owner_net_cents', 'commission_rate', 'margin_safe'],
            ],
        ]);

        $this->assertGreaterThan(0, $response->json('tax_gross_up_cents'));
        $this->assertGreaterThan(0, $response->json('stay_discount_cents'));
        $this->assertSame(0.10, $response->json('length_discount_rate'));
    }
}
