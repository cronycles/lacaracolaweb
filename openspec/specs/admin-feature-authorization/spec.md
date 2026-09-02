# admin-feature-authorization Specification

## Purpose
TBD - created by archiving change role-based-access-control. Update Purpose after archive.
## Requirements
### Requirement: Route authorization checks
The system SHALL enforce permission checks on admin routes. Users without required permissions SHALL be denied access and redirected or shown an error.

#### Scenario: Host keeper denied access to pricing routes
- **WHEN** a host_keeper attempts POST to `/admin/prezzi`
- **THEN** a 403 error is shown or user is redirected to dashboard

#### Scenario: Host keeper denied access to accounting routes
- **WHEN** a host_keeper attempts GET `/admin/contabilita`
- **THEN** access is denied

#### Scenario: Super admin can access all routes
- **WHEN** a super_admin user accesses any admin route
- **THEN** all routes are accessible

### Requirement: Booking feature authorization
Host keeper users SHALL only view bookings but not create, edit, delete, cancel, restore them. When viewing booking list or detail, host keeper SHALL NOT see `income_amount` (neither in the list nor in the detail). Host keeper SHALL NOT access the PDF import.

#### Scenario: Host keeper views bookings list
- **WHEN** a host_keeper navigates to `/admin/prenotazioni`
- **THEN** bookings are displayed in read-only mode; `income_amount` column is not shown

#### Scenario: Host keeper views booking detail
- **WHEN** a host_keeper views a booking at `/admin/prenotazioni/{id}`
- **THEN** the detail is shown read-only; `income_amount` field is not present; `cleaning_amount` and `linen_amount` are visible

#### Scenario: Host keeper cannot create booking
- **WHEN** a host_keeper attempts POST to `/admin/prenotazioni`
- **THEN** action is denied (403)

#### Scenario: Host keeper cannot access PDF import
- **WHEN** a host_keeper navigates to `/admin/prenotazioni/import-pdf`
- **THEN** access is denied (403)

### Requirement: Calendar authorization
Host keeper users SHALL be able to view the calendar in read-only mode. They SHALL NOT create or delete availability blocks.

#### Scenario: Host keeper views calendar
- **WHEN** a host_keeper navigates to `/admin/calendario`
- **THEN** calendar is displayed in read-only mode (block creation UI is hidden)

#### Scenario: Host keeper cannot create block
- **WHEN** a host_keeper attempts POST to `/admin/blocchi`
- **THEN** the request is denied (403)

#### Scenario: Host keeper cannot delete block
- **WHEN** a host_keeper attempts DELETE to `/admin/blocchi/{block}`
- **THEN** the request is denied (403)

### Requirement: Pricing feature authorization
Host keeper users SHALL NOT access pricing or stay discount rules. All pricing-related routes SHALL be denied.

#### Scenario: Host keeper denied access to pricing
- **WHEN** a host_keeper navigates to `/admin/prezzi`
- **THEN** access is denied

#### Scenario: Host keeper denied access to stay discounts
- **WHEN** a host_keeper navigates to `/admin/sconti-soggiorno`
- **THEN** access is denied

### Requirement: Settings and newsletter authorization
Host keeper users SHALL NOT access settings, newsletter, or accounting features.

#### Scenario: Host keeper denied access to settings
- **WHEN** a host_keeper navigates to `/admin/impostazioni`
- **THEN** access is denied

#### Scenario: Host keeper denied access to newsletter
- **WHEN** a host_keeper navigates to `/admin/newsletter`
- **THEN** access is denied

#### Scenario: Host keeper denied access to accounting
- **WHEN** a host_keeper navigates to `/admin/contabilita`
- **THEN** access is denied

### Requirement: People/guests authorization
Host keeper users SHALL view the guest list and guest details in read-only mode.

#### Scenario: Host keeper views guests
- **WHEN** a host_keeper navigates to `/admin/ospiti`
- **THEN** guests are displayed but creation/edit/delete actions are disabled

#### Scenario: Host keeper cannot create guest
- **WHEN** a host_keeper attempts POST to `/admin/ospiti`
- **THEN** action is denied

