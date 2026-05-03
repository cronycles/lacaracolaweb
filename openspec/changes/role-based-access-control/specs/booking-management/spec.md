## MODIFIED Requirements

### Requirement: Booking visibility and editing
Super admin users MAY create, edit, cancel, and restore bookings. Host keeper users SHALL only view bookings in read-only mode and SHALL NOT see the income_amount field.

#### Scenario: Super admin can edit bookings
- **WHEN** a super_admin views a booking detail
- **THEN** edit and delete buttons are visible and functional

#### Scenario: Host keeper views booking read-only
- **WHEN** a host_keeper views a booking detail
- **THEN** edit and delete buttons are not visible; view is read-only

#### Scenario: Host keeper cannot see income amount
- **WHEN** a host_keeper views booking details at `/admin/prenotazioni/{id}`
- **THEN** the income_amount field is hidden; only cleaning_amount and linen_amount are visible

#### Scenario: Host keeper cannot access PDF import
- **WHEN** a host_keeper navigates to `/admin/prenotazioni/import-pdf`
- **THEN** access is denied (403 or redirect)
