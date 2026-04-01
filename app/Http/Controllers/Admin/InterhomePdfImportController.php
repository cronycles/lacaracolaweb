<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingCreationService;
use App\Services\InterhomePdfBookingParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class InterhomePdfImportController extends Controller
{
    private const PREVIEW_SESSION_KEY = 'interhome_pdf_import_preview';

    public function index(Request $request): View
    {
        return view('admin.interhome-pdf-import', [
            'preview' => $request->session()->get(self::PREVIEW_SESSION_KEY),
        ]);
    }

    public function preview(Request $request, InterhomePdfBookingParser $parser): View
    {
        $validated = $request->validate([
            'pdf_file' => ['required', 'file', 'mimetypes:application/pdf', 'max:10240'],
        ]);

        $file = $validated['pdf_file'];
        try {
            $parsed = $parser->parseFile($file->getRealPath());
        } catch (Throwable $e) {
            return view('admin.interhome-pdf-import', [
                'preview' => null,
            ])->withErrors([
                'pdf_file' => 'Impossibile leggere il PDF. Verifica che il file sia valido e riprova.',
            ]);
        }

        $rows = [];
        foreach ($parsed['rows'] as $row) {
            $status = $this->resolveImportStatus($row);
            $rows[] = array_merge($row, $status);
        }

        $preview = [
            'filename' => $file->getClientOriginalName(),
            'rows' => $rows,
            'warnings' => $parsed['warnings'],
            'counts' => [
                'total' => count($rows),
                'new' => count(array_filter($rows, fn (array $row): bool => $row['status'] === 'new')),
                'duplicate' => count(array_filter($rows, fn (array $row): bool => $row['status'] === 'duplicate')),
                'skipped' => count(array_filter($rows, fn (array $row): bool => $row['status'] === 'skipped')),
            ],
        ];

        $request->session()->put(self::PREVIEW_SESSION_KEY, $preview);

        return view('admin.interhome-pdf-import', compact('preview'));
    }

    public function confirm(Request $request, BookingCreationService $creationService): RedirectResponse
    {
        $preview = $request->session()->get(self::PREVIEW_SESSION_KEY);
        if (!$preview || empty($preview['rows'])) {
            return redirect()
                ->route('admin.bookings.import-pdf')
                ->with('error', 'Nessuna anteprima disponibile. Carica prima un PDF.');
        }

        $created = 0;
        $skipped = 0;

        foreach ($preview['rows'] as $row) {
            $currentStatus = $this->resolveImportStatus($row);
            if (($currentStatus['status'] ?? '') !== 'new') {
                $skipped++;
                continue;
            }

            $creationService->createFromParsed([
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'] ?: null,
                'phone' => $row['phone'] ?: null,
                'checkin' => $row['checkin'],
                'checkout' => $row['checkout'],
                'adults' => (int) $row['adults'],
                'children' => (int) $row['children'],
                'babies' => (int) $row['babies'],
                'source' => $row['source'] === 'owner' ? 'owner' : 'interhome',
                'external_ref' => $row['external_ref'] ?: null,
                'notes' => 'Imported from Interhome PDF: ' . ($preview['filename'] ?? 'unknown file'),
            ]);

            $created++;
        }

        $request->session()->forget(self::PREVIEW_SESSION_KEY);

        return redirect()
            ->route('admin.bookings.index')
            ->with('success', "Import completato. Nuove prenotazioni create: {$created}. Saltate: {$skipped}.");
    }

    /**
     * @param  array<string, mixed> $row
     * @return array{status: string, status_reason: string}
     */
    private function resolveImportStatus(array $row): array
    {
        if (!empty($row['skip_import'])) {
            return ['status' => 'skipped', 'status_reason' => (string) ($row['skip_reason'] ?? 'Riga non importabile')];
        }

        if (!empty($row['external_ref'])) {
            $existsByRef = Booking::where('external_ref', $row['external_ref'])->exists();
            if ($existsByRef) {
                return ['status' => 'duplicate', 'status_reason' => 'Rif. esterno già presente'];
            }
        }

        $existsByStayAndName = Booking::where('source', $row['source'] === 'owner' ? 'owner' : 'interhome')
            ->whereDate('checkin', $row['checkin'])
            ->whereDate('checkout', $row['checkout'])
            ->whereHas('person', function ($query) use ($row): void {
                $query->where('first_name', $row['first_name'])
                    ->where('last_name', $row['last_name']);
            })
            ->exists();

        if ($existsByStayAndName) {
            return ['status' => 'duplicate', 'status_reason' => 'Stesso ospite e stesse date già presenti'];
        }

        return ['status' => 'new', 'status_reason' => 'Pronta per import'];
    }
}
