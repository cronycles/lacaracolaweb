<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\GuestReportingDriverInterface;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\GuestReport;
use App\Models\Person;
use App\Services\GuestReporting\GuestRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GuestReportingController extends Controller
{
    public function __construct(private readonly GuestReportingDriverInterface $driver) {}

    /** List all past submissions (audit history). */
    public function index(): View
    {
        $reports = GuestReport::with('booking.person')
            ->orderByDesc('submitted_at')
            ->paginate(25);

        return view('admin.guest-reporting.index', compact('reports'));
    }

    /** Show the send form pre-filled with the booking's guest data. */
    public function show(Booking $prenotazioni): View
    {
        $prenotazioni->load('person', 'additionalGuests', 'guestReports');

        $lastReport = $prenotazioni->guestReports->sortByDesc('submitted_at')->first();

        return view('admin.guest-reporting.show', [
            'booking'    => $prenotazioni,
            'lastReport' => $lastReport,
        ]);
    }

    /** Persist guest data updates then test the draft against the SOAP service. */
    public function saveAndTest(Request $request, Booking $prenotazioni): RedirectResponse
    {
        $guests = $this->validateAndPersistGuests($request, $prenotazioni);
        $result = $this->driver->testDraft($guests);

        GuestReport::create([
            'booking_id'     => $prenotazioni->id,
            'driver'         => config('guest-reporting.default'),
            'mode'           => 'test',
            'status'         => $result->success ? 'success' : 'error',
            'guests_count'   => count($guests),
            'guests_payload' => array_map(fn (GuestRecord $g) => (array) $g, $guests),
            'soap_response'  => $result->rawResponse ? json_decode($result->rawResponse, true) : null,
            'error_message'  => $result->message,
            'submitted_at'   => now(),
        ]);

        return redirect()
            ->route('admin.guest-reporting.show', $prenotazioni)
            ->with($result->success ? 'success' : 'error', $result->message)
            ->with('row_details', $result->rowDetails);
    }

    /** Persist guest data updates then send definitively to the SOAP service. */
    public function saveAndSend(Request $request, Booking $prenotazioni): RedirectResponse
    {
        $guests = $this->validateAndPersistGuests($request, $prenotazioni);
        $result = $this->driver->sendGuests($guests);

        GuestReport::create([
            'booking_id'     => $prenotazioni->id,
            'driver'         => config('guest-reporting.default'),
            'mode'           => 'send',
            'status'         => $result->success ? 'success' : 'error',
            'guests_count'   => count($guests),
            'guests_payload' => array_map(fn (GuestRecord $g) => (array) $g, $guests),
            'soap_response'  => $result->rawResponse ? json_decode($result->rawResponse, true) : null,
            'error_message'  => $result->message,
            'submitted_at'   => now(),
        ]);

        return redirect()
            ->route('admin.guest-reporting.show', $prenotazioni)
            ->with($result->success ? 'success' : 'error', $result->message)
            ->with('row_details', $result->rowDetails);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Validate the submitted guest data, persist each Person's fields,
     * and return an array of GuestRecord DTOs ready for the driver.
     *
     * @return GuestRecord[]
     */
    private function validateAndPersistGuests(Request $request, Booking $booking): array
    {
        $countryCodes = array_keys(config('apartment.guest_countries', []));

        $data = $request->validate([
            'guests'                                      => ['required', 'array', 'min:1'],
            'guests.*.person_id'                          => ['required', 'integer', 'exists:people,id'],
            'guests.*.include'                            => ['sometimes', 'nullable'],
            'guests.*.tipo_alloggiato'                    => ['required_if:guests.*.include,1', 'nullable', 'string', Rule::in(['16', '17', '18', '19', '20'])],
            'guests.*.gender'                             => ['required_if:guests.*.include,1', 'nullable', 'string', Rule::in(['M', 'F'])],
            'guests.*.birth_date'                         => ['required_if:guests.*.include,1', 'nullable', 'date'],
            'guests.*.birth_municipality'                 => ['required_if:guests.*.include,1', 'nullable', 'string', 'max:100'],
            'guests.*.birth_province'                     => ['nullable', 'string', 'max:2'],
            'guests.*.birth_country_code'                 => ['required_if:guests.*.include,1', 'nullable', 'string', Rule::in($countryCodes)],
            'guests.*.nationality_code'                   => ['required_if:guests.*.include,1', 'nullable', 'string', Rule::in($countryCodes)],
            'guests.*.document_type'                      => ['required_if:guests.*.include,1', 'nullable', 'string', Rule::in(['passport', 'id_card', 'driving_license', 'residence_permit', 'other'])],
            'guests.*.document_number'                    => ['required_if:guests.*.include,1', 'nullable', 'string', 'max:60'],
            'guests.*.document_issue_place'               => ['nullable', 'string', 'max:100', 'required_if:guests.*.document_issue_country_code,IT'],
            'guests.*.document_issue_country_code'        => ['required_if:guests.*.include,1', 'nullable', 'string', Rule::in($countryCodes)],
        ]);

        $guestRecords = [];

        foreach ($data['guests'] as $guestData) {
            // Skip guests deselected via the "Includi" checkbox
            if (empty($guestData['include'])) {
                continue;
            }

            // Persist updated person fields permanently
            $person = Person::findOrFail((int) $guestData['person_id']);
            $person->update([
                'gender'                      => $guestData['gender'],
                'birth_municipality'          => $guestData['birth_municipality'],
                'birth_province'              => $guestData['birth_province'] ?? null,
                'birth_country_code'          => $guestData['birth_country_code'],
                'nationality_code'            => $guestData['nationality_code'],
                'document_type'               => $guestData['document_type'],
                'document_number'             => $guestData['document_number'],
                'document_issue_place'        => $guestData['document_issue_place'],
                'document_issue_country_code' => $guestData['document_issue_country_code'],
            ]);

            $guestRecords[] = new GuestRecord(
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
                documentType:              $guestData['document_type'],
                documentNumber:            $guestData['document_number'],
                documentIssuePlace:        $guestData['document_issue_place'],
                documentIssueCountryCode:  $guestData['document_issue_country_code'],
            );
        }

        if (empty($guestRecords)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'guests' => ['Seleziona almeno un ospite da includere nell\'invio.'],
            ]);
        }

        return $guestRecords;
    }
}
