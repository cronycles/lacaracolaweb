<?php

declare(strict_types=1);

namespace App\Services\GuestReporting;

/**
 * Immutable DTO carrying one guest's data in internal application codes.
 *
 * All fields use the app's own vocabulary (ISO-2 country codes, internal
 * document type strings). Each driver is responsible for mapping these to
 * whatever the external service requires — this class never changes when
 * switching drivers.
 */
readonly class GuestRecord
{
    /**
     * @param string       $tipoAlloggiato  '16' Italian head of group
     *                                      '17' Italian family member
     *                                      '18' Foreign head of group
     *                                      '19' Foreign family member
     * @param string       $lastName        Guest surname
     * @param string       $firstName       Guest first name
     * @param string       $gender          'M' or 'F'
     * @param string       $birthDate       'Y-m-d' format
     * @param string       $birthMunicipality  Municipality name (free text); driver resolves code
     * @param string|null  $birthProvince   2-char province abbreviation (Italian guests only)
     * @param string       $birthCountryCode   ISO 3166-1 alpha-2
     * @param string       $nationalityCode    ISO 3166-1 alpha-2
     * @param string       $documentType    'passport'|'id_card'|'driving_license'|'residence_permit'|'other'
     * @param string       $documentNumber
     * @param string       $documentIssuePlace  Municipality name or country name
     * @param string       $documentIssueCountryCode  ISO 3166-1 alpha-2
     */
    public function __construct(
        public string $tipoAlloggiato,
        public string $lastName,
        public string $firstName,
        public string $gender,
        public string $birthDate,
        public string $birthMunicipality,
        public ?string $birthProvince,
        public string $birthCountryCode,
        public string $nationalityCode,
        public string $documentType,
        public string $documentNumber,
        public string $documentIssuePlace,
        public string $documentIssueCountryCode,
    ) {}
}
