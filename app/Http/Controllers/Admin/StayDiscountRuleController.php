<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StayDiscountRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StayDiscountRuleController extends Controller
{
    public function index(): View
    {
        $rules = StayDiscountRule::query()
            ->orderBy('priority')
            ->orderByDesc('min_nights')
            ->get();

        return view('admin.stay-discounts.index', compact('rules'));
    }

    public function create(): View
    {
        return view('admin.stay-discounts.form', ['rule' => new StayDiscountRule()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->assertNoActiveOverlap($data);

        StayDiscountRule::create($data);

        return redirect()
            ->route('admin.stay-discounts.index')
            ->with('success', 'Regola sconto soggiorno creata.');
    }

    public function edit(StayDiscountRule $stay_discount_rule): View
    {
        return view('admin.stay-discounts.form', ['rule' => $stay_discount_rule]);
    }

    public function update(Request $request, StayDiscountRule $stay_discount_rule): RedirectResponse
    {
        $data = $this->validated($request);
        $this->assertNoActiveOverlap($data, $stay_discount_rule->id);

        $stay_discount_rule->update($data);

        return redirect()
            ->route('admin.stay-discounts.index')
            ->with('success', 'Regola sconto soggiorno aggiornata.');
    }

    public function destroy(StayDiscountRule $stay_discount_rule): RedirectResponse
    {
        $stay_discount_rule->delete();

        return redirect()
            ->route('admin.stay-discounts.index')
            ->with('success', 'Regola sconto soggiorno eliminata.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'min_nights' => ['required', 'integer', 'min:1', 'max:365'],
            'max_nights' => ['nullable', 'integer', 'min:1', 'max:365'],
            'discount_percent' => ['required', 'integer', 'min:1', 'max:90'],
            'is_active' => ['nullable', 'boolean'],
            'priority' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        if ($data['max_nights'] !== null && $data['max_nights'] < $data['min_nights']) {
            throw ValidationException::withMessages([
                'max_nights' => 'Il valore massimo notti deve essere uguale o maggiore del minimo.',
            ]);
        }

        return $data;
    }

    private function assertNoActiveOverlap(array $candidate, ?int $exceptId = null): void
    {
        if (! ($candidate['is_active'] ?? false)) {
            return;
        }

        $rules = StayDiscountRule::query()
            ->active()
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->get();

        $candidateMin = (int) $candidate['min_nights'];
        $candidateMax = $candidate['max_nights'] === null ? PHP_INT_MAX : (int) $candidate['max_nights'];

        foreach ($rules as $rule) {
            $ruleMin = (int) $rule->min_nights;
            $ruleMax = $rule->max_nights === null ? PHP_INT_MAX : (int) $rule->max_nights;

            if ($candidateMin <= $ruleMax && $ruleMin <= $candidateMax) {
                throw ValidationException::withMessages([
                    'min_nights' => 'Intervallo notti sovrapposto a un\'altra regola attiva.',
                ]);
            }
        }
    }
}
