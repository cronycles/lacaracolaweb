<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PricingRule;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalPriceTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            \Database\Seeders\PermissionSeeder::class,
            \Database\Seeders\RoleSeeder::class,
        ]);
    }

    public function test_super_admin_can_view_the_portal_price_table(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $admin = User::factory()->create(['role_id' => $superAdminRole->id]);

        PricingRule::create([
            'start_month' => 1,
            'start_day' => 1,
            'end_month' => 6,
            'end_day' => 30,
            'price_per_night' => 10000,
        ]);
        PricingRule::create([
            'start_month' => 7,
            'start_day' => 1,
            'end_month' => 12,
            'end_day' => 31,
            'price_per_night' => 15000,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.pricing.portal-prices'));

        $response->assertOk();
        $response->assertViewIs('admin.pricing.portal-prices');
        $response->assertViewHas('rules', fn ($rules) => $rules->count() === 2);
        $response->assertViewHas('portalRates', fn ($portalRates) => $portalRates->every(
            fn (array $rates): bool => array_keys($rates) === ['airbnb', 'booking', 'hometogo']
        ));
    }

    public function test_route_requires_manage_pricing_permission(): void
    {
        $hostKeeperRole = Role::where('name', 'host_keeper')->first();
        $hostKeeper = User::factory()->create(['role_id' => $hostKeeperRole->id]);

        $this->actingAs($hostKeeper)
            ->get(route('admin.pricing.portal-prices'))
            ->assertRedirect('/admin/');
    }
}
