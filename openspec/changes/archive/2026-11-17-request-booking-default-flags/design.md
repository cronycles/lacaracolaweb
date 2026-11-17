## Context

Accepting a pending website availability request creates and persists a booking before redirecting the owner to its edit form. The edit form reflects persisted inclusion and tax-declaration flags for existing bookings. Cleaning and linen inclusion currently use the database's false defaults; tax flags are initialized by `Booking::booted()` from `finance.tax_declaration_defaults`, where cleaning and linen are currently false.

The requested defaults are specific to website-request conversion. Manually entered bookings and Interhome imports must retain their current defaults. The selected flags do not mark payments as received or paid; accounting movements continue to require their own payment statuses and dates.

## Goals / Non-Goals

**Goals:**

- Initialize cleaning and linen inclusion, and income, cleaning, and linen tax-declaration flags, as true when converting a website request.
- Preserve the current parking defaults and all defaults for other booking creation paths.
- Persist the values during conversion so the edit form displays them selected immediately; keep them editable.
- Leave existing bookings unchanged.

**Non-Goals:**

- Changing payment statuses or payment dates.
- Changing global finance configuration, database schema, or migration behavior.
- Changing manual booking or Interhome import defaults.
- Updating previously converted bookings.

## Decisions

- Set the five request-specific flags explicitly in `BookingRequestController::confirm()` when creating the booking. This path-specific override is preferred over changing `finance.tax_declaration_defaults` or model-wide defaults, which would affect manual and imported bookings.
- Keep `income_includes_parking` at its existing false default and omit `parking_tax` from the request-specific override so the model's configured parking default remains authoritative.
- Rely on the existing edit form to render persisted values and accept later changes; no separate presentation-only default is needed.
- Update product and backend documentation to describe the request-conversion exception to otherwise unchanged defaults.

## Risks / Trade-offs

- [Risk] Selected inclusion flags can fail the existing included-amount validation if service amounts exceed the booking amount → Website quotes include the cleaning and linen amounts in the quoted house total; retain the validation and cover request conversion with a focused test.
- [Risk] Tax flags could be mistaken for received payments → Preserve payment booleans and dates unchanged; document that the flags only classify paid movements for the tax declaration.

