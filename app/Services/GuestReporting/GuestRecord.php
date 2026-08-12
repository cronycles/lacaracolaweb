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
     * @param string       $tipoAlloggiato  '16'=ospite singolo, '17'=capo famiglia,
     *                                      '18'=capo gruppo, '19'=familiare, '20'=membro gruppo
     * @param string       $arrivalDate     Check-in date in 'd/m/Y' format (from booking)
     * @param int          $stayNights      Number of nights (from booking, max 30)
     * @param string       $lastName        Guest surname
     * @param string       $firstName       Guest first name
     * @param string       $gender          'M' or 'F'
     * @param string       $birthDate       'Y-m-d' format
     * @param string       $birthMunicipality  Municipality name (free text); driver resolves 9-digit code
     * @param string|null  $birthProvince   2-char province abbreviation (Italian births only)
     * @param string       $birthCountryCode   ISO 3166-1 alpha-2
     * @param string       $nationalityCode    ISO 3166-1 alpha-2
     * @param string       $documentType    'passport'|'id_card'|'driving_license'|'other'
     * @param string       $documentNumber  (blank for tipo 19-20)
     * @param string       $documentIssuePlace  Municipality name or country name (blank for tipo 19-20)
     * @param string       $documentIssueCountryCode  ISO 3166-1 alpha-2 (blank for tipo 19-20)
     */
    public function __construct(
        public string  $tipoAlloggiato,
        public string  $arrivalDate,
        public int     $stayNights,
        public string  $lastName,
        public string  $firstName,
        public string  $gender,
        public string  $birthDate,
        public string  $birthMunicipality,
        public ?string $birthProvince,
        public string  $birthCountryCode,
        public string  $nationalityCode,
        public string  $documentType,
        public string  $documentNumber,
        public string  $documentIssuePlace,
        public string  $documentIssueCountryCode,
    ) {}

    public function withDates(string $arrivalDate, int $stayNights): self
    {
        return new self(
            tipoAlloggiato: $this->tipoAlloggiato,
            arrivalDate: $arrivalDate,
            stayNights: $stayNights,
            lastName: $this->lastName,
            firstName: $this->firstName,
            gender: $this->gender,
            birthDate: $this->birthDate,
            birthMunicipality: $this->birthMunicipality,
            birthProvince: $this->birthProvince,
            birthCountryCode: $this->birthCountryCode,
            nationalityCode: $this->nationalityCode,
            documentType: $this->documentType,
            documentNumber: $this->documentNumber,
            documentIssuePlace: $this->documentIssuePlace,
            documentIssueCountryCode: $this->documentIssueCountryCode,
        );
    }
}
