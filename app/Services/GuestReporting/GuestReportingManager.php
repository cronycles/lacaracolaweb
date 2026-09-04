<?php

declare(strict_types=1);

namespace App\Services\GuestReporting;

use App\Contracts\GuestReportingDriverInterface;
use InvalidArgumentException;

/**
 * Resolves and delegates to the configured guest-reporting driver.
 *
 * To add a new driver:
 * 1. Implement GuestReportingDriverInterface in a new class under App\Services\GuestReporting.
 * 2. Add a config block for it in config/guest-reporting.php under 'drivers'.
 * 3. Register it in the match() expression below.
 * 4. Set GUEST_REPORTING_DRIVER=your_key in .env.
 *
 * See openspec/changes/guest-reporting/design.md § "How to Add a New Driver".
 */
class GuestReportingManager implements GuestReportingDriverInterface
{
    private GuestReportingDriverInterface $driver;

    public function __construct()
    {
        $driverKey = config('guest-reporting.default');
        $driverConfig = config("guest-reporting.drivers.{$driverKey}", []);

        $this->driver = $this->resolve((string) $driverKey, (array) $driverConfig);
    }

    public function checkConnection(): bool
    {
        return $this->driver->checkConnection();
    }

    public function testDraft(array $guests): SubmissionResult
    {
        return $this->driver->testDraft($guests);
    }

    public function sendGuests(array $guests): SubmissionResult
    {
        return $this->driver->sendGuests($guests);
    }

    private function resolve(string $driverKey, array $config): GuestReportingDriverInterface
    {
        return match ($driverKey) {
            'polizia_stato' => new PoliziaStatoAlloggiatiDriver($config),
            default => throw new InvalidArgumentException("Unknown guest-reporting driver: [{$driverKey}]"),
        };
    }
}
