<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Services\GuestReporting\GuestRecord;
use App\Services\GuestReporting\SubmissionResult;

/**
 * Common interface for all guest-reporting drivers.
 *
 * The application layer (controllers, views) interacts exclusively with this
 * interface — it knows nothing about SOAP, positional records, or any specific
 * government portal. To add a new driver, implement this interface and register
 * it in config/guest-reporting.php. See design.md § "How to Add a New Driver".
 */
interface GuestReportingDriverInterface
{
    /**
     * Verify that credentials are valid and the service is reachable.
     * Uses the driver's internal authentication check (e.g. Authentication_Test).
     */
    public function checkConnection(): bool;

    /**
     * Validate guest records against the service without submitting them.
     * Returns per-row validation details so errors can be shown before final send.
     *
     * @param  GuestRecord[]  $guests
     */
    public function testDraft(array $guests): SubmissionResult;

    /**
     * Submit guest records to the reporting authority.
     *
     * @param  GuestRecord[]  $guests
     */
    public function sendGuests(array $guests): SubmissionResult;
}
