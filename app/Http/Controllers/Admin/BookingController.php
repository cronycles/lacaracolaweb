<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BookingConfirmedMail;
use App\Mail\BookingHostKeeperMail;
use App\Mail\BookingPaymentReceivedMail;
use App\Mail\CheckinReminderMail;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Person;
use App\Models\User;
use App\Services\TelegramService;
use App\Services\TelegramBookingMessageBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        // Load bookings (guest reservations)
        $bookings = Booking::with('person')
            ->orderByDesc('checkin')
            ->get()
            ->each(function ($booking) {
                $booking->_type = 'booking';
            });

        // Load personal blocks (owner/maintenance blocks not linked to bookings)
        $personalBlocks = AvailabilityBlock::whereNull('booking_id')
            ->orderByDesc('start_date')
            ->get()
            ->each(function ($block) {
                $block->_type = 'block';
            });

        // Merge and sort all items by date (checkin/start_date)
        // Use concat() instead of merge() to avoid key collisions: Eloquent collections
        // are keyed by primary key, so merge() would overwrite a Booking with an
        // AvailabilityBlock that share the same numeric ID.
        $allItems = $bookings
            ->concat($personalBlocks)
            ->sortByDesc(function ($item) {
                return $item->_type === 'booking' ? $item->checkin : $item->start_date;
            })
            ->values();

        $perPage = 20;
        $page = (int) $request->input('page', 1);
        $items = new LengthAwarePaginator(
            $allItems->forPage($page, $perPage),
            $allItems->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.bookings.index', compact('items'));
    }

    public function create(): View
    {
        $people = Person::orderBy('last_name')->orderBy('first_name')->get();

        return view('admin.bookings.form', ['booking' => new Booking, 'people' => $people]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $person = Person::findOrFail($data['person_id']);
        $person->autoSubscribeToNewsletter();

        // Tax flag defaults (for users without view_accounting who don't see the
        // checkboxes) are applied by Booking::booted()'s `creating` listener,
        // shared by every booking-creation path.
        $booking = Booking::create($data);

        // Automatically create availability block
        AvailabilityBlock::create([
            'start_date' => $data['checkin'],
            'end_date' => $data['checkout'],
            'reason' => 'booked',
            'booking_id' => $booking->id,
        ]);

        return redirect()->route('admin.bookings.show', $booking)->with('success', 'Prenotazione aggiunta.');
    }

    public function show(Booking $prenotazioni): View
    {
        $prenotazioni->load('person', 'additionalGuests', 'bookingRequest');

        // People selectable as additional guests: never in any booking, or previously with this capogruppo
        $selectablePeople = Person::selectableForCapogruppo($prenotazioni->person_id)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('admin.bookings.show', [
            'booking' => $prenotazioni,
            'selectablePeople' => $selectablePeople,
        ]);
    }

    public function edit(Booking $prenotazioni): View
    {
        $people = Person::orderBy('last_name')->orderBy('first_name')->get();

        return view('admin.bookings.form', ['booking' => $prenotazioni, 'people' => $people]);
    }

    public function update(Request $request, Booking $prenotazioni): RedirectResponse
    {
        $data = $this->validated($request);
        $person = Person::findOrFail($data['person_id']);
        $person->autoSubscribeToNewsletter();
        $prenotazioni->update($data);

        // Sync the linked availability block dates
        if ($prenotazioni->availabilityBlock) {
            $prenotazioni->availabilityBlock->update([
                'start_date' => $data['checkin'],
                'end_date' => $data['checkout'],
            ]);
        }

        return redirect()->route('admin.bookings.index')->with('success', 'Prenotazione aggiornata.');
    }

    public function destroy(Booking $prenotazioni): RedirectResponse
    {
        $prenotazioni->availabilityBlock?->delete();
        $prenotazioni->delete();

        return redirect()->route('admin.bookings.index')->with('success', 'Prenotazione eliminata.');
    }

    public function cancel(Booking $prenotazioni): RedirectResponse
    {
        if (! $prenotazioni->isCanceled()) {
            $prenotazioni->update(['canceled_at' => now()]);
        }

        return redirect()->back()->with('success', 'Prenotazione segnata come cancellata. Giorni liberati.');
    }

    /**
     * Manually send the "booking confirmed — pay within 48h" email to the guest,
     * with payment instructions and the free-cancellation deadline. CCs the owner
     * and sends a separate operational summary to all host keepers.
     */
    public function sendConfirmationEmail(Booking $prenotazioni): RedirectResponse
    {
        $prenotazioni->load('person', 'bookingRequest');

        if (empty($prenotazioni->person->email)) {
            return redirect()->back()->with('error', "Impossibile inviare: l'ospite non ha un indirizzo email.");
        }

        if (! User::paymentOwner()) {
            return redirect()->back()->with('error', 'Impossibile inviare: nessun host owner abilitato ai pagamenti.');
        }

        Mail::to($prenotazioni->person->email)->send(new BookingConfirmedMail($prenotazioni));

        $hostKeeperEmails = User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'host_keeper'))
            ->whereNotNull('email')
            ->pluck('email')
            ->all();

        if ($hostKeeperEmails !== []) {
            Mail::to($hostKeeperEmails)->send(new BookingHostKeeperMail($prenotazioni));
        }

        $prenotazioni->update(['confirmation_sent_at' => now()]);

        return redirect()->back()->with('success', 'Email di conferma prenotazione inviata a '.$prenotazioni->person->email.'.');
    }

    /**
     * Manually (re)send the online check-in reminder email, e.g. for testing or
     * to nudge a guest ahead of the automatic reminder sent by the
     * `checkin:send-reminders` scheduled command (config: reminder_lead_days).
     */
    public function sendCheckinReminderEmail(Booking $prenotazioni): RedirectResponse
    {
        $prenotazioni->load('person');

        if (empty($prenotazioni->person->email)) {
            return redirect()->back()->with('error', "Impossibile inviare: l'ospite non ha un indirizzo email.");
        }

        Mail::to($prenotazioni->person->email)->send(new CheckinReminderMail($prenotazioni));
        $prenotazioni->update(['checkin_reminder_sent_at' => now()]);

        return redirect()->back()->with('success', 'Email di promemoria check-in online inviata a '.$prenotazioni->person->email.'.');
    }

    /**
    * Mark the payment as received and send the corresponding guest email.
     */
    public function sendPaymentReceivedEmail(Booking $prenotazioni): RedirectResponse
    {
        $prenotazioni->load('person');

        if (empty($prenotazioni->person->email)) {
            return redirect()->back()->with('error', "Impossibile inviare: l'ospite non ha un indirizzo email.");
        }

        Mail::to($prenotazioni->person->email)->send(new BookingPaymentReceivedMail($prenotazioni));
        $prenotazioni->update([
            'income_paid' => true,
            'income_paid_at' => $prenotazioni->income_paid_at ?? today(),
            'payment_received_sent_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Email di pagamento ricevuto inviata a '.$prenotazioni->person->email.'.');
    }

    public function notifyTelegram(
        Booking $prenotazioni,
        TelegramService $telegram,
        TelegramBookingMessageBuilder $messages,
    ): JsonResponse
    {
        if ($telegram->countRecipients() === 0) {
            return response()->json(['sent' => false, 'reason' => 'no_recipients']);
        }

        $telegram->sendToAllRecipients($messages->buildBookingSummary($prenotazioni));
        $prenotazioni->update(['telegram_notified_at' => now()]);

        return response()->json(['sent' => true]);
    }

    public function restore(Booking $prenotazioni): RedirectResponse
    {
        if ($prenotazioni->isCanceled()) {
            $hasBookingConflict = Booking::query()
                ->whereKeyNot($prenotazioni->id)
                ->whereNull('canceled_at')
                ->whereDate('checkin', '<', $prenotazioni->checkout)
                ->whereDate('checkout', '>', $prenotazioni->checkin)
                ->exists();

            $hasManualBlockConflict = AvailabilityBlock::query()
                ->whereNull('booking_id')
                ->whereDate('start_date', '<', $prenotazioni->checkout)
                ->whereDate('end_date', '>=', $prenotazioni->checkin)
                ->exists();

            if ($hasBookingConflict || $hasManualBlockConflict) {
                return redirect()->back()->with('error', 'Ripristino non possibile: il periodo risulta già occupato.');
            }

            $prenotazioni->update(['canceled_at' => null]);
        }

        return redirect()->back()->with('success', 'Cancellazione rimossa. Prenotazione di nuovo attiva.');
    }

    // Personal blocks management (owner/maintenance)
    public function showBlock(AvailabilityBlock $block): View
    {
        return view('admin.bookings.show-block', compact('block'));
    }

    public function editBlock(AvailabilityBlock $block): View
    {
        return view('admin.bookings.form-block', compact('block'));
    }

    public function updateBlock(Request $request, AvailabilityBlock $block): RedirectResponse
    {
        $data = $this->validatedBlock($request);
        $block->update($data);

        return redirect()->route('admin.bookings.index')->with('success', 'Blocco aggiornato.');
    }

    public function destroyBlock(AvailabilityBlock $block): RedirectResponse
    {
        $block->delete();

        return redirect()->route('admin.bookings.index')->with('success', 'Blocco rimosso.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'person_id' => ['required', 'exists:people,id'],
            'checkin' => ['required', 'date'],
            'checkout' => ['required', 'date', 'after:checkin'],
            'adults' => ['required', 'integer', 'min:1', 'max:6'],
            'children' => ['nullable', 'integer', 'min:0', 'max:6'],
            'babies' => ['nullable', 'integer', 'min:0', 'max:6'],
            'pets' => ['nullable', 'integer', 'min:0', 'max:4'],
            'source' => ['required', 'in:direct,airbnb,booking,interhome'],
            'external_ref' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'income_amount' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'income_paid' => ['nullable', 'boolean'],
            'income_paid_at' => ['nullable', 'date'],
            'cleaning_amount' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'cleaning_paid' => ['nullable', 'boolean'],
            'linen_amount' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'linen_paid' => ['nullable', 'boolean'],
            'parking_amount' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'parking_paid' => ['nullable', 'boolean'],
            'parking_paid_at' => ['nullable', 'date'],
            'services_paid_at' => ['nullable', 'date'],
            'income_tax' => ['nullable', 'boolean'],
            'cleaning_tax' => ['nullable', 'boolean'],
            'linen_tax' => ['nullable', 'boolean'],
            'parking_tax' => ['nullable', 'boolean'],
        ]);
    }

    private function validatedBlock(Request $request): array
    {
        return $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'in:owner,maintenance'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
