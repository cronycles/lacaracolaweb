## Why

When an owner accepts an availability request from the website, the resulting booking opens in the edit form with cleaning and linen inclusion, and their tax-declaration flags, not matching the intended defaults. The owner must correct these settings for every such booking, creating avoidable bookkeeping risk.

## What Changes

- Set cleaning and linen inclusion to selected for bookings created by accepting a website request.
- Set the income, cleaning, and linen tax-declaration flags to selected for those bookings.
- Preserve the current parking defaults, keep the fields editable, and leave existing bookings and other booking creation paths unchanged.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `booking-request-conversion`: define request-specific inclusion and tax-flag defaults when converting a website request.
- `booking-accounting-receipts`: allow cleaning and linen inclusion to be enabled by default for website-request conversions while preserving defaults for other creation paths.
- `booking-tax-flags`: define request-specific tax-flag defaults while preserving existing defaults for other creation paths.

## Impact

- `BookingRequestController::confirm()` booking initialization and its feature tests.
- Admin booking edit form behavior after accepting a request.
- Product and technical documentation describing request conversion and booking defaults.
- No database schema, route, or dependency changes.
