<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Contracts\GuestReportingDriverInterface;
use App\Services\GuestReporting\SubmissionResult;

/**
 * Fake driver used by guest-reporting feature tests to count calls and
 * capture submitted guest payloads without hitting a real reporting service.
 *
 * Declared as a named, top-level class (not extending TestCase) so its
 * `testDraft` method is not mistaken for a PHPUnit test method by tooling.
 */
class FakeGuestReportingDriver implements GuestReportingDriverInterface
{
    public function __construct(
        private int &$callCounter,
        private array &$driverGuests,
    ) {}

    public function checkConnection(): bool
    {
        $this->callCounter++;

        return true;
    }

    public function testDraft(array $guests): SubmissionResult
    {
        $this->callCounter++;
        $this->driverGuests = $guests;

        return SubmissionResult::success('OK');
    }

    public function sendGuests(array $guests): SubmissionResult
    {
        $this->callCounter++;

        return SubmissionResult::success('OK');
    }
}
