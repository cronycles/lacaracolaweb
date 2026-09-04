<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * `admin/sconti-soggiorno` is a static "Formula prezzi" explainer page, not an editable rule set
 * — the weekly/monthly discount percentages live in Settings, not in a `StayDiscountRule` table.
 * The `StayDiscountRule` model/migration are dormant by design (see
 * openspec/changes/archive/2026-09-02-tax-gross-up-pricing/design.md); this controller only
 * renders the explainer, with no CRUD actions to keep in sync with it.
 */
class StayDiscountRuleController extends Controller
{
    public function index(): View
    {
        return view('admin.stay-discounts.index');
    }
}
