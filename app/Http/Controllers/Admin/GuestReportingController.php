<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\GuestReportingDriverInterface;
use App\Http\Controllers\Concerns\PersistsGuestReportingData;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Country;
use App\Models\GuestReport;
use App\Models\GuestType;
use App\Models\Person;
use App\Services\GuestReporting\Data\ItalianMunicipalities;
use App\Services\GuestReporting\GuestRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GuestReportingController extends Controller
{
    use PersistsGuestReportingData;

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

        $selectablePeople = Person::selectableForCapogruppo($prenotazioni->person_id)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('admin.guest-reporting.show', [
            'booking' => $prenotazioni,
            'lastReport' => $lastReport,
            'comuniNames' => ItalianMunicipalities::allValidNames(),
            'countries' => Country::whereNotNull('iso2')->orderBy('name_it')->pluck('name_it', 'iso2')->toArray(),
            'guestTypes' => GuestType::orderBy('code')->get(),
            'selectablePeople' => $selectablePeople,
        ]);
    }

    /** Persist guest data updates then test the draft against the SOAP service. */
    public function saveAndTest(Request $request, Booking $prenotazioni): RedirectResponse
    {
        $guests = $this->validateAndPersistGuests($request, $prenotazioni);

        return $this->runTest($guests, $prenotazioni, 'test');
    }

    /** Persist guest data updates then test the draft using dates accepted by Alloggiati Web. */
    public function saveAndTestWithSimulatedDates(Request $request, Booking $prenotazioni): RedirectResponse
    {
        $guests = $this->validateAndPersistGuests($request, $prenotazioni);
        $simulatedArrival = today()->format('d/m/Y');
        $guests = array_map(
            fn (GuestRecord $guest) => $guest->withDates($simulatedArrival, 3),
            $guests,
        );

        return $this->runTest($guests, $prenotazioni, 'test_simulated');
    }

    /** @param GuestRecord[] $guests */
    private function runTest(array $guests, Booking $booking, string $mode): RedirectResponse
    {
        $result = $this->driver->testDraft($guests);

        GuestReport::create([
            'booking_id' => $booking->id,
            'driver' => config('guest-reporting.default'),
            'mode' => $mode,
            'status' => $result->success ? 'success' : 'error',
            'guests_count' => count($guests),
            'guests_payload' => array_map(fn (GuestRecord $g) => (array) $g, $guests),
            'soap_response' => $result->rawResponse ? json_decode($result->rawResponse, true) : null,
            'error_message' => $result->message,
            'submitted_at' => now(),
        ]);

        return redirect()
            ->route('admin.guest-reporting.show', $booking)
            ->with($result->success ? 'success' : 'error', $result->message)
            ->with('row_details', $result->rowDetails);
    }

    /** Persist guest data updates then send definitively to the SOAP service. */
    public function saveAndSend(Request $request, Booking $prenotazioni): RedirectResponse
    {
        $guests = $this->validateAndPersistGuests($request, $prenotazioni);
        $result = $this->driver->sendGuests($guests);

        GuestReport::create([
            'booking_id' => $prenotazioni->id,
            'driver' => config('guest-reporting.default'),
            'mode' => 'send',
            'status' => $result->success ? 'success' : 'error',
            'guests_count' => count($guests),
            'guests_payload' => array_map(fn (GuestRecord $g) => (array) $g, $guests),
            'soap_response' => $result->rawResponse ? json_decode($result->rawResponse, true) : null,
            'error_message' => $result->message,
            'submitted_at' => now(),
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
        $this->normalizeGuestReportingTypes($request);
        $countryCodes = Country::whereNotNull('iso2')->pluck('iso2')->all();

        $data = $request->validate($this->guestReportingValidationRules($request, $countryCodes));

        $guestRecords = [];

        foreach ($data['guests'] as $guestData) {
            // Skip guests deselected via the "Includi" checkbox
            if (empty($guestData['include'])) {
                continue;
            }

            $guestRecords[] = $this->persistGuestPerson($guestData, $booking);
        }

        if (empty($guestRecords)) {
            throw ValidationException::withMessages([
                'guests' => ['Seleziona almeno un ospite da includere nell\'invio.'],
            ]);
        }

        return $guestRecords;
    }

    /** Delete one or more guest report history rows belonging to the given booking. */
    public function destroyReports(Request $request, Booking $prenotazioni): RedirectResponse
    {
        $data = $request->validate([
            'report_ids' => ['required', 'array', 'min:1'],
            'report_ids.*' => ['required', 'integer'],
        ]);

        GuestReport::where('booking_id', $prenotazioni->id)
            ->whereIn('id', $data['report_ids'])
            ->delete();

        return redirect()
            ->route('admin.guest-reporting.show', $prenotazioni)
            ->with('success', 'Righe eliminate dallo storico.');
    }
}
