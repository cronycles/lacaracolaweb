<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExternalCalendarProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalCalendarProvider>
 */
class ExternalCalendarProviderFactory extends Factory
{
    protected $model = ExternalCalendarProvider::class;

    public function definition(): array
    {
        return [
            'key' => 'airbnb',
            'url' => fake()->url(),
            'enabled' => true,
            'sync_status' => 'success',
            'last_sync_attempt_at' => now(),
            'last_successful_sync_at' => now(),
            'imported_event_count' => 0,
            'latest_error' => null,
        ];
    }

    public function neverSynced(): static
    {
        return $this->state(fn (): array => [
            'sync_status' => 'never_synced',
            'last_sync_attempt_at' => null,
            'last_successful_sync_at' => null,
        ]);
    }
}
