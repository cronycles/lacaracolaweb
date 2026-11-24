## Purpose

Allow guests to book and pay immediately while preserving availability and existing direct-booking protections.

## ADDED Requirements

### Requirement: Third public booking mode
Authorized administrators SHALL be able to select external booking, owner-approved requests, or direct Stripe payment. Direct payment SHALL be selectable only when Stripe is explicitly activated and configured; existing in-flight bookings SHALL retain their original flow after mode changes.

#### Scenario: Direct payment mode selected
- **WHEN** a guest selects dates in direct payment mode
- **THEN** the site collects the existing required guest details and consent and offers payment without owner approval

#### Scenario: Integration not ready
- **WHEN** an administrator selects direct payment without a ready, activated Stripe integration
- **THEN** the selection is rejected with a clear explanation and the current mode remains active

### Requirement: Mobile-first booking and payment
Public booking and payment workflows and related admin controls SHALL be designed mobile-first, remain usable at narrow viewport widths, and adapt to larger screens without obscured content or horizontal overflow.

#### Scenario: Guest books on a phone
- **WHEN** a guest completes booking and payment on a narrow mobile viewport
- **THEN** dates, prices, consent, payment controls and status messages are legible, accessible and operable without horizontal scrolling

### Requirement: Availability and price are required
The system SHALL validate guest details, legal consent, stay limits, availability, and a valid server-side price before starting a direct payment. Dates without a calculable price SHALL be presented as unavailable for direct booking.

#### Scenario: Missing price
- **WHEN** a selected stay has no valid price
- **THEN** direct booking and payment are unavailable and no reservation or payment is created

#### Scenario: Concurrent attempts
- **WHEN** two guests attempt to reserve overlapping dates at the same time
- **THEN** at most one acquires the hold and the other is informed that the dates are unavailable

### Requirement: Temporary direct payment hold
The system SHALL hold the selected dates while a checkout is open, initially for 30 minutes or the configured duration, and shall keep the hold while payment is processing. Expired, abandoned, or failed unpaid checkouts SHALL release the hold when no payment can still succeed.

#### Scenario: Checkout abandoned
- **WHEN** the hold expires without a completed or processing payment and the checkout can no longer be paid
- **THEN** the dates become available and no abandonment email is sent

#### Scenario: Payment succeeds
- **WHEN** a valid payment succeeds and there is no conflicting external booking
- **THEN** one confirmed booking is created or finalized and the guest, owner, and host keepers receive appropriate confirmation communications

### Requirement: Fallback on payment service outage
If Stripe is unavailable before checkout can be initiated, the system SHALL offer the guest the option of submitting a normal owner-approved request, clearly labeled as not yet confirmed or paid.

#### Scenario: Guest chooses the fallback
- **WHEN** checkout cannot be started because Stripe is unavailable and the guest chooses a manual request
- **THEN** the standard pending request is created only after revalidating availability and consent

#### Scenario: Payment outcome is uncertain
- **WHEN** a payment might already have been initiated or completed
- **THEN** the site does not create a duplicate fallback request or charge and presents an actionable status
