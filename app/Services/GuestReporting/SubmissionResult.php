<?php

declare(strict_types=1);

namespace App\Services\GuestReporting;

/**
 * Immutable DTO returned by every driver operation.
 *
 * Controllers and views interact only with this class — never with raw SOAP
 * responses or driver-specific data structures.
 */
readonly class SubmissionResult
{
    /**
     * @param  bool  $success  True if the external service accepted all records
     * @param  string  $message  Human-readable summary (shown directly in admin UI, Italian)
     * @param  array  $rowDetails  Per-row details from the service response.
     *                             Each item: ['row' => int, 'esito' => string, 'descrizione' => string]
     * @param  string|null  $rawResponse  JSON-encoded raw response for audit storage; null on transport error
     */
    public function __construct(
        public bool $success,
        public string $message,
        public array $rowDetails = [],
        public ?string $rawResponse = null,
    ) {}

    public static function failure(string $message, ?string $rawResponse = null): self
    {
        return new self(false, $message, [], $rawResponse);
    }

    public static function success(string $message, array $rowDetails = [], ?string $rawResponse = null): self
    {
        return new self(true, $message, $rowDetails, $rawResponse);
    }
}
