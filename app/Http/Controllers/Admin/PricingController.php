<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function index(): View
    {
        $rules = PricingRule::orderBy('start_date')->get();

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

    /** Shared validation for store/update */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:80'],
            'start_date'      => ['required', 'date'],
            'end_date'        => ['required', 'date', 'after:start_date'],
            'price_per_night' => ['required', 'integer', 'min:1', 'max:99999'],
            'min_nights'      => ['required', 'integer', 'min:1', 'max:30'],
        ]);

        // Store cents (input is euros)
        $data['price_per_night'] = $data['price_per_night'] * 100;

        return $data;
    }
}
