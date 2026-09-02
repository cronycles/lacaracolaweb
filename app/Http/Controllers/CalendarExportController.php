<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\IcalCalendarExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CalendarExportController extends Controller
{
    public function __invoke(Request $request, IcalCalendarExportService $calendarExportService): Response
    {
        $expectedToken = (string) config('apartment.calendar.export_token');
        $providedToken = (string) $request->query('t', '');

        if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            return response('', 403);
        }

        return response($calendarExportService->generate(), 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=calendar.ics',
        ]);
    }
}
