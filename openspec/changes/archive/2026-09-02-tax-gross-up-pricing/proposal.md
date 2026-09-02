## Why

The owner operates as a private landlord (no VAT number, "Cedolare Secca" flat-rate
tax). Under this regime, tax is owed on the *entire* amount charged to the guest,
including pass-through accessory costs — the cleaning fee and linen fee handed over
to the housekeeper/laundry. Today `PricingQuoteService` computes the guest total as
simply `stay + cleaning + linen`, so every euro collected for cleaning/linen actually
costs the owner `tax_rate` more than what they keep — the tax on those accessory
costs is currently absorbed by the owner instead of being priced into the total.

Separately, the owner lists the same apartment on Airbnb, Booking.com and HomeToGo,
each of which deducts a commission from the payout. To net the same revenue as the
direct-site price, the listed price on each portal must be higher, and there is
currently no tool computing this — it's worked out manually and inconsistently.

On all 3 portals the owner also configures a native length-of-stay discount (10%
for 7+ nights, 20% for 28+ nights) that portals apply automatically to qualifying
bookings. The direct site has no equivalent: a dormant `StayDiscountRule` model/
table/CRUD exists in the codebase but was intentionally disconnected from pricing
(its admin page — `admin/sconti-soggiorno` — is documented as "read-only
 informational, no editable discount rules", see `docs/business-doc.mdc`). The
owner now wants a real equivalent discount on direct bookings too, and the portal
price suggestion must account for the portal's own discount so the base rate
typed into each portal still nets the right amount.

## What Changes

- Add a "Fiscalità" section to the existing admin Settings page (`admin/settings`),
  backed by the existing key-value `Setting` store (no new table):
  - Global tax rate (percentage, default `21%`, editable without a deploy).
  - Which accessory cost items (cleaning fee, linen fee, parking fee) are subject to
    the tax gross-up (default: cleaning + linen checked, parking unchecked, since
    parking is collected locally in cash and isn't part of the taxed bank transfer).
- **BREAKING (pricing behavior)**: `PricingQuoteService::calculate()` grosses up the
  nightly-stay portion of the total by `tax_rate × (sum of selected accessory costs)`,
  then rounds the guest-facing grand total to the nearest €5, folding the rounding
  delta back into the stay portion so the breakdown still sums exactly to the total.
  This changes the REAL amount quoted/charged for direct-site bookings (the public
  quote, the `booking_requests` price estimate, and the confirmed `Booking.income_amount`)
  — not just a report.
- Add "Portal commission" settings (Airbnb, Booking.com, HomeToGo — percentages),
  also stored via `Setting`, with defaults sourced from the owner-provided analysis in
  `docs/commissioni portali.md` (Airbnb 15.5%, Booking.com 16.5%, HomeToGo 15.5%
  "Flex" model). That file's content is folded into this change's `design.md` and the
  file is deleted from `docs/` once the change is merged.
- Extend the admin pricing simulator (`admin/pricing/index.blade.php` +
  `PricingController::simulate`) to show, alongside the existing breakdown: the tax
  gross-up line item, the length-of-stay discount applied (if any), and — for each
  of the 3 portals — the suggested listing price (total for the simulated stay and
  average/night), computed as
  `direct_price_with_tax / ((1 - length_discount_rate) * (1 - commission_rate))`.
- **BREAKING (pricing behavior)**: add a real length-of-stay discount to the
  direct-site price — `10%` for stays of 7+ nights, `20%` for stays of 28+ nights
  (the higher tier replaces, not stacks with, the lower one) — applied only to the
  nightly-stay portion (not cleaning/linen), both percentages editable from
  Settings, thresholds fixed in code. The same percentages are assumed to be
  configured identically on all 3 portals and used in the portal price formula
  above. The pre-existing, disconnected `StayDiscountRule` scaffold is left
  untouched and out of scope (it modeled arbitrary tiered rules; this simpler,
  fixed 7/28-night model supersedes its purpose without reusing its code).
- Update the "Formula prezzi" page (`admin/stay-discounts/index.blade.php`) to
  document the new tax gross-up + length-of-stay discount + portal markup
  formulas.

## Capabilities

### New Capabilities
- `tax-gross-up-pricing`: settings-driven tax rate and selectable accessory cost
  items; `PricingQuoteService` applies the gross-up and €5 rounding to the guest
  total used across the whole direct-booking flow.
- `ota-portal-pricing`: settings-driven per-portal commission rates and computation
  of suggested Airbnb/Booking.com/HomeToGo listing prices — accounting for both
  commission and the portal's own length-of-stay discount — displayed in the admin
  pricing simulator.
- `stay-length-discount-pricing`: settings-driven weekly (7+ nights) and monthly
  (28+ nights) discount percentages, applied to the nightly-stay portion of the
  real direct-booking price.

### Modified Capabilities
_None — `PricingQuoteService`'s current behavior was never captured as an OpenSpec
spec, so there is no existing delta to apply._

## Impact

- `app/Services/PricingQuoteService.php` — core calculation changes.
- `app/Http/Controllers/Admin/PricingController.php`, `Admin/SettingsController.php`
  — new settings persistence + simulate endpoint response fields.
- `app/Models/Setting.php` — reused as-is (no schema change).
- `resources/views/admin/settings.blade.php`,
  `resources/views/admin/pricing/index.blade.php`,
  `resources/views/admin/stay-discounts/index.blade.php` — UI changes.
- `resources/ts/components/pricing-simulator.ts` — render new response fields.
- `config/apartment.php` — no change (values move to `Setting`, not config).
- `docs/commissioni portali.md` — deleted (superseded by `design.md`).
- Downstream: `Public\BookingController::requestAvailability` (quote persisted on
  `booking_requests`), `Admin\BookingRequestController::confirm()` (copies estimate
  onto `Booking.income_amount`) — no code change expected, but their output values
  change because `PricingQuoteService` now returns higher `stay_cents`.
- `app/Services/OtaPortalPricingService.php` — new service, includes the portal
  length-discount factor.
- Tests: `PricingQuoteService` unit tests, `BookingLegalConsentTest`,
  `BookingRequestConfirmationTest` (existing tests asserting exact price values will
  need updated expected amounts).
