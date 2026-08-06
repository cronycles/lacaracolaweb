## ADDED Requirements

### Requirement: Guest self-service data entry reusing admin classification logic
The public check-in form SHALL let the booking's primary guest fill/edit their own personal and document data, and add travel companions (up to the booking's total guest count) as new `Person` records, using the same `tipo_alloggiato` auto-defaulting logic as `admin/guest-reporting` (single guest → type 16; first of several → type 18 "Capo gruppo"; remaining companions → type 20 "Membro gruppo"), sourced from one shared implementation used by both the admin and public forms.

#### Scenario: Single guest defaults to type 16
- **WHEN** a booking has exactly one total guest and the guest opens the check-in form
- **THEN** that guest is classified as type 16 ("Ospite singolo") and document fields are required

#### Scenario: Multiple guests default to 18 + 20
- **WHEN** a booking has more than one guest
- **THEN** the primary guest is classified as type 18 ("Capo gruppo", document required) and every companion added is classified as type 20 ("Membro gruppo", no document required)

#### Scenario: Cannot exceed the booking's total guest count
- **WHEN** the guest attempts to add a companion beyond the booking's total guest count (adults + children)
- **THEN** the system rejects the addition with a clear message

### Requirement: Guest-submitted data is only saved, never auto-reported to AlloggiatiWeb
Data submitted through the public check-in form SHALL be persisted to the corresponding `Person` records exactly like an admin edit, and SHALL NOT trigger any submission to the AlloggiatiWeb SOAP service.

#### Scenario: Submission only updates Person records
- **WHEN** the guest submits the check-in form
- **THEN** the system updates/creates the relevant `Person` records and does not call the guest-reporting driver

### Requirement: Explicit completion step, editable afterwards
The guest SHALL explicitly confirm completion via a dedicated action, which sets `Booking.checkin_completed_at`. The guest MAY continue to edit the data afterwards, as long as the check-in token has not expired.

#### Scenario: Confirming sets the completed timestamp
- **WHEN** the guest clicks "Confirm & submit" after filling all required data for every guest
- **THEN** the system sets `checkin_completed_at` to the current time

#### Scenario: Editing after confirmation is still allowed
- **WHEN** the guest returns to a valid (non-expired) check-in link after having already confirmed
- **THEN** the system lets them view and edit the previously submitted data without needing to reconfirm for the change to be saved

#### Scenario: Incomplete data blocks confirmation
- **WHEN** the guest attempts to confirm while any required guest (per their classification's document requirements) has missing required fields
- **THEN** the system rejects the confirmation and highlights the missing fields
