<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\FinancialAttachment;
use App\Models\FinancialEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinancialAttachmentController extends Controller
{
    /** Resolve the parent model from the URL type slug. */
    private function resolveModel(string $type, int $id): FinancialEntry|Booking
    {
        return match ($type) {
            'entry'   => FinancialEntry::findOrFail($id),
            'booking' => Booking::findOrFail($id),
            default   => abort(404),
        };
    }

    public function store(Request $request, string $type, int $id): RedirectResponse
    {
        $request->validate([
            'attachment' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
                'max:10240', // 10 MB
            ],
        ]);

        $model = $this->resolveModel($type, $id);

        $file     = $request->file('attachment');
        $ext      = $file->getClientOriginalExtension();
        $folder   = 'finance-attachments/' . $type . 's/' . $id;
        $filename = Str::uuid() . ($ext ? '.' . $ext : '');

        Storage::disk('local')->put($folder . '/' . $filename, file_get_contents($file->getRealPath()));

        $model->attachments()->create([
            'original_name' => $file->getClientOriginalName(),
            'stored_path'   => $folder . '/' . $filename,
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
        ]);

        return redirect()->back()->with('success', 'Allegato aggiunto.');
    }

    public function download(FinancialAttachment $attachment): BinaryFileResponse
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($attachment->stored_path)) {
            abort(404, 'File non trovato.');
        }

        return response()->download(
            $disk->path($attachment->stored_path),
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type ?? 'application/octet-stream'],
        );
    }

    public function destroy(FinancialAttachment $attachment): RedirectResponse
    {
        Storage::disk('local')->delete($attachment->stored_path);
        $attachment->delete();

        return redirect()->back()->with('success', 'Allegato eliminato.');
    }
}
