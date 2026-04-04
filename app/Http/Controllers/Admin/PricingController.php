<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingRule;
use App\Services\PricingQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function index(): View
    {
        $rules = PricingRule::orderBy('start_month')
            ->orderBy('start_day')
            ->get();

        return view('admin.pricing.index', compact('rules'));
    }

    public function create(): View
    {
        return view('admin.pricing.form', ['rule' => new PricingRule()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        PricingRule::create($data);

        return redirect()->route('admin.pricing.index')->with('success', 'Regola di prezzo creata.');
    }

    public function edit(PricingRule $prezzi): View
    {
        return view('admin.pricing.form', ['rule' => $prezzi]);
    }

    public function update(Request $request, PricingRule $prezzi): RedirectResponse
    {
        $data = $this->validated($request);
        $prezzi->update($data);

        return redirect()->route('admin.pricing.index')->with('success', 'Regola aggiornata.');
    }

    public function destroy(PricingRule $prezzi): RedirectResponse
    {
        $prezzi->delete();

        return redirect()->route('admin.pricing.index')->with('success', 'Regola eliminata.');
    }

    public function bulkAdjust(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'operation' => ['required', 'in:add,subtract'],
            'amount_eur' => ['required', 'integer', 'min:1', 'max:99999'],
        ]);

        $deltaCents = ((int) $data['amount_eur']) * 100;
        if ($data['operation'] === 'subtract') {
            $deltaCents *= -1;
        }

        $rulesCount = PricingRule::count();
        if ($rulesCount === 0) {
            return redirect()->route('admin.pricing.index')->with('error', 'Nessuna regola prezzo da aggiornare.');
        }

        $minCurrent = (int) PricingRule::min('price_per_night');
        if (($minCurrent + $deltaCents) <= 0) {
            return redirect()->route('admin.pricing.index')->with('error', 'Operazione non valida: almeno una regola andrebbe a 0€ o meno.');
        }

        PricingRule::query()->update([
            'price_per_night' => DB::raw('price_per_night + (' . $deltaCents . ')'),
        ]);

        $verb = $deltaCents > 0 ? 'aumentati' : 'ridotti';
        $amount = number_format(abs($deltaCents) / 100, 0, ',', '.');

        return redirect()->route('admin.pricing.index')->with('success', "Prezzi {$verb} in bulk di {$amount}€ per notte su tutte le regole.");
    }

    public function simulate(Request $request, PricingQuoteService $pricingQuoteService): JsonResponse
    {
        $data = $request->validate([
            'checkin' => ['required', 'date'],
            'checkout' => ['required', 'date', 'after:checkin'],
        ]);

        $checkin = new \DateTimeImmutable($data['checkin']);
        $checkout = new \DateTimeImmutable($data['checkout']);
        $nights = (int) $checkin->diff($checkout)->days;
        $minNights = (int) config('apartment.booking.min_nights', 3);

        if ($nights < $minNights) {
            return response()->json([
                'available' => false,
                'message' => __('app.error_min_nights', ['nights' => $minNights]),
            ]);
        }

        $quote = $pricingQuoteService->calculate($data['checkin'], $data['checkout']);

        if (! $quote['available']) {
            return response()->json([
                'available' => false,
                'message' => __('app.booking_price_unavailable'),
            ]);
        }

        $stayCents = (int) ($quote['stay_cents'] ?? 0);
        $discountPercent = (int) ($quote['discount_percent'] ?? 0);
        $discountCents = (int) ($quote['discount_cents'] ?? 0);
        $discountedStayCents = (int) ($quote['discounted_stay_cents'] ?? $stayCents);
        $cleaningCents = ((int) config('apartment.booking.cleaning_fee', 0)) * 100;
        $totalCents = $discountedStayCents + $cleaningCents;

        return response()->json([
            'available' => true,
            'nights' => $nights,
            'stay_cents' => $stayCents,
            'discount_percent' => $discountPercent,
            'discount_cents' => $discountCents,
            'discounted_stay_cents' => $discountedStayCents,
            'cleaning_cents' => $cleaningCents,
            'total_cents' => $totalCents,
        ]);
    }

    /** Shared validation for store/update */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'start_month'     => ['required', 'integer', 'min:1', 'max:12'],
            'start_day'       => ['required', 'integer', 'min:1', 'max:31'],
            'end_month'       => ['required', 'integer', 'min:1', 'max:12'],
            'end_day'         => ['required', 'integer', 'min:1', 'max:31'],
            'price_per_night' => ['required', 'integer', 'min:1', 'max:99999'],
        ]);

        $errors = new MessageBag();
        if (! checkdate((int) $data['start_month'], (int) $data['start_day'], 2000)) {
            $errors->add('start_day', 'Data di inizio non valida.');
        }

        if (! checkdate((int) $data['end_month'], (int) $data['end_day'], 2000)) {
            $errors->add('end_day', 'Data di fine non valida.');
        }

        if ($errors->isNotEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors->toArray());
        }

        // Store cents (input is euros)
        $data['price_per_night'] = $data['price_per_night'] * 100;

        return $data;
    }
}
