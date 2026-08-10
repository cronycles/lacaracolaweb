<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Models\Booking;
use App\Models\Person;
use App\Services\GuestReporting\Data\ItalianMunicipalities;
use App\Services\GuestReporting\GuestClassifier;
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
        $rules = [
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
            'guests.*.birth_country_code'                 => ['required_if:guests.*.include,1', 'nullable', 'string', Rule::in($countryCodes)],
            'guests.*.nationality_code'                   => ['required_if:guests.*.include,1', 'nullable', 'string', Rule::in($countryCodes)],
        ];

        // Document fields need explicit per-index rules (rather than the
        // `guests.*` wildcard used above) because their "required" condition
        // is a compound check (guest included AND tipo_alloggiato requires a
        // document) that the `required_if` rule string can't express — and a
        // plain Closure is NOT an "implicit" rule, so Laravel silently skips
        // it (along with every other non-implicit rule) whenever `nullable`
        // is present and the field is empty. `Rule::requiredIf()` IS
        // implicit, so — unlike a Closure — it is always evaluated, which is
        // what actually makes the document requirement enforceable here.
        foreach ((array) $request->input('guests', []) as $idx => $guestRow) {
            $guestRow = is_array($guestRow) ? $guestRow : [];
            $included = ! empty($guestRow['include'] ?? null);
            $requiresDoc = $included && GuestClassifier::requiresDocument((string) ($guestRow['tipo_alloggiato'] ?? ''));
            $birthCountryIsItaly = ($guestRow['birth_country_code'] ?? null) === 'IT';
            $issueCountryIsItaly = ($guestRow['document_issue_country_code'] ?? null) === 'IT';

            $rules["guests.{$idx}.birth_province"] = [
                Rule::requiredIf($included && $birthCountryIsItaly), 'nullable', 'string', 'max:2',
            ];
            $rules["guests.{$idx}.document_type"] = [
                Rule::requiredIf($requiresDoc), 'nullable', 'string',
                Rule::in(['passport', 'id_card', 'driving_license', 'residence_permit', 'other']),
            ];
            $rules["guests.{$idx}.document_number"] = [
                Rule::requiredIf($requiresDoc), 'nullable', 'string', 'max:60',
            ];
            $rules["guests.{$idx}.document_issue_country_code"] = [
                Rule::requiredIf($requiresDoc), 'nullable', 'string', Rule::in($countryCodes),
            ];
            $rules["guests.{$idx}.document_issue_place"] = [
                Rule::requiredIf($requiresDoc && $issueCountryIsItaly), 'nullable', 'string', 'max:100',
                function ($attribute, $value, $fail) {
                    if (filled($value) && ItalianMunicipalities::findCode($value) === null) {
                        $fail("'{$value}' non è un comune italiano riconosciuto per il luogo di rilascio.");
                    }
                },
            ];
        }

        return $rules;
    }

    /** Normalize human-readable guest fields before exact municipality validation. */
    protected function normalizeGuestReportingInput(Request $request): void
    {
        $guests = (array) $request->input('guests', []);

        foreach ($guests as $idx => $guest) {
            if (! is_array($guest)) {
                continue;
            }

            if (isset($guest['birth_municipality']) && is_string($guest['birth_municipality'])) {
                $guest['birth_municipality'] = mb_convert_case(mb_strtolower(trim($guest['birth_municipality'])), MB_CASE_TITLE, 'UTF-8');
            }

            if (isset($guest['birth_province']) && is_string($guest['birth_province'])) {
                $guest['birth_province'] = mb_strtoupper(trim($guest['birth_province']), 'UTF-8');
            }

            $guests[$idx] = $guest;
        }

        $request->merge(['guests' => $guests]);
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
