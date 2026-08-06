## Context

`App\Http\Controllers\Public\BookingController::requestAvailability` already creates a `BookingRequest` row with everything needed to start a booking: `name`, `email`, `phone`, `checkin`, `checkout`, `adults`, `children`, `message`, `locale` (added in `booking-online-checkin`), plus legal-consent proof (`terms_accepted_at`, `ip_address`, `user_agent`). Nothing in the codebase reads this table back except the one-time owner notification email (`BookingRequestMail`).

`App\Services\BookingCreationService::createFromParsed()` (used by the Interhome PDF import flow, `Admin\InterhomePdfImportController`) already implements exactly the "find-or-create guest" strategy this change needs: match by email, then by phone, then by exact first+last name, else create a new `Person`; on a match, enrich missing `email`/`phone` from the new data. It also creates the `Booking` + `AvailabilityBlock` in one call. This change reuses that logic rather than reimplementing it, extending its input contract to accept `BookingRequest`-shaped data (no `pets`/`babies`/`external_ref`, has `message`/`locale` instead).

`bookings.booking_request_id` (nullable FK, added in `prenotazione-privata-legal-checkbox`) already exists but has never been populated — this change is the first to actually set it.

## Goals / Non-Goals

**Goals:**
- Let the owner see all pending availability requests in one place in admin, with a one-click path to a real `Booking`.
- Reuse the existing, already-battle-tested guest-matching logic instead of duplicating it.
- Preview which `Person` a confirmation would use, before committing to it.
- Let the owner explicitly dismiss a request they won't be converting, so the queue stays a clean, actionable to-do list.
- Leave all financial fields (pricing, cleaning, linen, parking) empty on the created booking — the owner fills these in manually on the booking edit page afterward, exactly like today's manual creation flow. No auto-pricing.

**Non-Goals:**
- No automatic emailing of the "booking confirmed" email as part of this action — the owner still triggers that separately (existing `sendConfirmationEmail` button), only after reviewing/completing the booking.
- No fuzzy/approximate name matching (e.g. Levenshtein/soundex) — only exact matches, consistent with `BookingCreationService`.
- No UI to edit/merge a wrongly-matched `Person` from the queue itself — if the match is wrong, the owner corrects `person_id` on the created booking's existing edit form (already supports changing it).
- No changes to the public request form or the request-received emails.

## Decisions

1. **Reuse `BookingCreationService`'s matching strategy, extracted into a public, reusable method.**
   `createFromParsed()` currently inlines the email → phone → exact-name matching logic before building the `Booking`. Extract just the matching+enrichment part into a new public method (e.g. `findOrCreatePerson(array $data): Person`), used by both `createFromParsed()` (unchanged behavior) and the new `BookingRequestController`. This keeps a single source of truth for "how we identify a returning guest from partial contact data," per the same principle already applied to `tipo_alloggiato` classification in `booking-online-checkin`.
   - *Alternative considered*: write a separate matching method for this new flow — rejected, would duplicate logic that already works and is tested indirectly via the Interhome import.

2. **Preview computed read-only, not cached/persisted.**
   The pending-queue view calls the same `findOrCreatePerson`-equivalent *lookup* (without creating anything) per row to show "→ Mario Rossi (profilo esistente)" or "→ verrà creato un nuovo profilo". Since this is a pure read (email/phone/name lookups), it's cheap enough to run per row on each page load; no caching needed at this scale (a handful of pending requests at a time).
   - *Risk*: between preview and the actual "Conferma" click, a matching `Person` could theoretically be created by another process — acceptable at this scale (single-owner admin panel), not worth a locking/queuing mechanism.

3. **`declined_at` (nullable timestamp) on `booking_requests` instead of deleting the row.**
   Keeps the legal-consent audit trail (`terms_accepted_at`, `ip_address`, `user_agent`) intact for declined requests too, consistent with the non-destructive philosophy already used for bookings (`canceled_at`) rather than hard deletes.

4. **Pending queue definition**: `BookingRequest` rows where `declined_at IS NULL` AND no `Booking` references them (`whereDoesntHave('booking')`). Ordered oldest-first (FIFO — oldest unanswered request surfaces first), like a to-do list.

5. **"Conferma" redirects straight to `admin.bookings.edit`** (not `.show`), since the very next thing the owner does is almost always fill in pricing/services — matches the existing manual-creation flow's end state (`create` → `store` → `.show`, but here we want the owner to land where they can immediately edit financials). Actually landing on `.edit` saves a click versus `.show` → "Modifica".

6. **New `Admin\BookingRequestController`** with `index` (queue), `confirm` (creates booking, redirects to edit), `decline` (sets `declined_at`) — mirrors the existing `Admin\BookingGuestController`-style thin-controller pattern used elsewhere in this codebase.

7. **Permission**: reuse `manage_bookings` (same permission gating booking creation/edit/cancel today) — no new permission needed; a request is meaningless without also being able to act on bookings.

8. **Sidebar menu entry "Richieste"** with a badge showing the pending count (`BookingRequest::pending()->count()`), visible to any user with `manage_bookings`, next to "Prenotazioni" in the admin nav.

## Risks / Trade-offs

- [Risk] Exact first+last-name matching (no email/phone provided or matched) could incorrectly link a new request to an unrelated existing `Person` with the same common name (e.g. two "Mario Rossi") → Mitigation: the preview makes the match visible *before* confirming, and the resulting booking's `person_id` remains editable afterward on the existing edit form; this is the same accepted risk already live in production for Interhome imports.
- [Risk] Populating `booking_request_id` for the first time means any future admin UI assuming it's always null would break → Mitigation: grep confirms nothing currently reads/relies on it being null; `BookingConfirmationEmailTest`/other tests unaffected since they create bookings directly without a request.
- [Trade-off] No fuzzy matching means near-duplicate profiles (typoed email, different phone format) can still occur — acceptable, matches the existing Interhome-import trade-off; not introducing new risk beyond what's already in production.

## Migration Plan

- New migration: add `declined_at` (nullable timestamp) to `booking_requests`.
- No backfill needed: existing `BookingRequest` rows either already have a linked `Booking` created manually (in which case `booking_request_id` will remain unset retroactively — out of scope to backfill/guess old links) or are simply old/stale and will appear in the new pending queue until declined or superseded.
- Rollback: standard `down()` migration dropping the column.

## Open Questions

- None outstanding — matching strategy, decline action, menu placement, and match preview were confirmed with the product owner before writing this design.
