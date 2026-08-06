<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Models\Booking;
use App\Models\Person;
use App\Services\GuestReporting\Data\ItalianMunicipalities;
use App\Services\GuestReporting\GuestRecord;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Shared guest-reporting validation + persistence logic, reused by the admin
 * `Admin\GuestReportingController` and the public `Public\CheckinController`.
 *
 * This trait only ever persists `Person` fields and builds `GuestRecord` DTOs —
 * it never submits anything to the AlloggiatiWeb SOAP driver. Callers decide
 * separately whether/when an actual submission happens.
 */
trait PersistsGuestReportingData
{
    /**
     * Laravel validation rules for a `guests` array of guest-reporting fields.
     * Identical for every caller; `$request` is needed by the closures to read
     * sibling fields for the same row index.
     *
     * @param string[] $countryCodes
     */
    protected function guestReportingValidationRules(Request $request, array $countryCodes): array
    {
        return [
            'guests'                                      => ['required', 'array', 'min:1'],
            'guests.*.person_id'                          => ['required', 'integer', 'exists:people,id'],
            'guests.*.include'                            => ['sometimes', 'nullable'],
            'guests.*.tipo_alloggiato'                    => ['required_if:guests.*.include,1', 'nullable', 'string', Rule::in(['16', '17', '18', '19', '20'])],
            'guests.*.gender'                             => ['required_if:guests.*.include,1', 'nullable', 'string', Rule::in(['M', 'F'])],
            'guests.*.birth_date'                         => ['required_if:guests.*.include,1', 'nullable', 'date'],
            'guests.*.birth_municipality'                 => [
                'required_if:guests.*.include,1', 'nullable', 'string', 'max:100',
                function ($attribute, $value, $fail) use ($request) {
                    $idx = explode('.', $attribute)[1];
                    if ($request->input("guests.{$idx}.birth_country_code") === 'IT'
                        && filled($value) && ItalianMunicipalities::findCode($value) === null) {
                        $fail("'{$value}' non è un comune italiano riconosciuto. Selezionare il nome dalla lista.");
                    }
                },
            ],
            'guests.*.birth_province'                     => ['nullable', 'string', 'max:2'],
            'guests.*.birth_country_code'                 => ['required_if:guests.*.include,1', 'nullable', 'string', Rule::in($countryCodes)],
            'guests.*.nationality_code'                   => ['required_if:guests.*.include,1', 'nullable', 'string', Rule::in($countryCodes)],
            'guests.*.document_type'                      => [
                function ($attribute, $value, $fail) use ($request) {
                    $idx = explode('.', $attribute)[1];
                    if (! empty($request->input("guests.{$idx}.include"))
                        && in_array($request->input("guests.{$idx}.tipo_alloggiato"), ['16', '17', '18'], true)
                        && empty($value)) {
                        $fail('Il tipo documento è obbligatorio per questo tipo di alloggiato.');
                    }
                },
                'nullable', 'string', Rule::in(['passport', 'id_card', 'driving_license', 'residence_permit', 'other']),
            ],
            'guests.*.document_number'                    => [
                function ($attribute, $value, $fail) use ($request) {
                    $idx = explode('.', $attribute)[1];
                    if (! empty($request->input("guests.{$idx}.include"))
                        && in_array($request->input("guests.{$idx}.tipo_alloggiato"), ['16', '17', '18'], true)
                        && empty($value)) {
                        $fail('Il numero documento è obbligatorio per questo tipo di alloggiato.');
                    }
                },
                'nullable', 'string', 'max:60',
            ],
            'guests.*.document_issue_place'               => [
                'nullable', 'string', 'max:100',
                function ($attribute, $value, $fail) use ($request) {
                    $idx = explode('.', $attribute)[1];
                    if (! empty($request->input("guests.{$idx}.include"))
                        && in_array($request->input("guests.{$idx}.tipo_alloggiato"), ['16', '17', '18'], true)
                        && $request->input("guests.{$idx}.document_issue_country_code") === 'IT'
                        && empty($value)) {
                        $fail('Il luogo di rilascio è obbligatorio per documenti italiani.');
                    }
                    if (filled($value) && ItalianMunicipalities::findCode($value) === null) {
                        $fail("'{$value}' non è un comune italiano riconosciuto per il luogo di rilascio.");
                    }
                },
            ],
            'guests.*.document_issue_country_code'        => [
                function ($attribute, $value, $fail) use ($request) {
                    $idx = explode('.', $attribute)[1];
                    if (! empty($request->input("guests.{$idx}.include"))
                        && in_array($request->input("guests.{$idx}.tipo_alloggiato"), ['16', '17', '18'], true)
                        && empty($value)) {
                        $fail('Lo stato di rilascio del documento è obbligatorio per questo tipo di alloggiato.');
                    }
                },
                'nullable', 'string', Rule::in($countryCodes),
            ],
        ];
    }

    /**
     * Persist one guest's `Person` fields (permanent update) and build the
     * corresponding `GuestRecord` DTO from the validated row data.
     */
    protected function persistGuestPerson(array $guestData, Booking $booking): GuestRecord
    {
        $person = Person::findOrFail((int) $guestData['person_id']);
        $person->update([
            'gender'                      => $guestData['gender'],
            'birth_date'                  => $guestData['birth_date'] ?? $person->birth_date,
            'birth_municipality'          => $guestData['birth_municipality'],
            'birth_province'              => $guestData['birth_province'] ?? null,
            'birth_country_code'          => $guestData['birth_country_code'],
            'nationality_code'            => $guestData['nationality_code'],
            'document_type'               => $guestData['document_type'] ?? null,
            'document_number'             => $guestData['document_number'] ?? null,
            'document_issue_place'        => $guestData['document_issue_place'] ?? null,
            'document_issue_country_code' => $guestData['document_issue_country_code'] ?? null,
        ]);

        return new GuestRecord(
            tipoAlloggiato:            $guestData['tipo_alloggiato'],
            arrivalDate:               $booking->checkin->format('d/m/Y'),
            stayNights:                $booking->nights,
            lastName:                  $person->last_name,
            firstName:                 $person->first_name,
            gender:                    $guestData['gender'],
            birthDate:                 $person->birth_date?->format('Y-m-d') ?? $guestData['birth_date'],
            birthMunicipality:         $guestData['birth_municipality'],
            birthProvince:             $guestData['birth_province'] ?? null,
            birthCountryCode:          $guestData['birth_country_code'],
            nationalityCode:           $guestData['nationality_code'],
            documentType:              $guestData['document_type'] ?? '',
            documentNumber:            $guestData['document_number'] ?? '',
            documentIssuePlace:        $guestData['document_issue_place'] ?? '',
            documentIssueCountryCode:  $guestData['document_issue_country_code'] ?? '',
        );
    }
}
