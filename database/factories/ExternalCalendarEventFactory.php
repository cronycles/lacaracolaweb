<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExternalCalendarEvent;
use App\Models\ExternalCalendarProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<ExternalCalendarEvent>
 */
class ExternalCalendarEventFactory extends Factory
{
    protected $model = ExternalCalendarEvent::class;

    public function definition(): array
    {
        $startDate = Carbon::instance(fake()->dateTimeBetween('+1 day', '+1 year'))->startOfDay();

        return [
            'external_calendar_provider_id' => ExternalCalendarProvider::factory(),
            'external_uid' => fake()->uuid(),
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addDays(fake()->numberBetween(1, 14)),
        ];
    }
}