# booking-management Specification

## Purpose
TBD - created by archiving change role-based-access-control. Update Purpose after archive.
## Requirements
### Requirement: Booking visibility and editing
Super admin users MAY create, edit, cancel, and restore bookings. Host keeper users SHALL only view bookings in read-only mode. The field `income_amount` SHALL NOT be visible to host keeper users **anywhere** (neither in the booking list nor in the booking detail). Only `cleaning_amount` and `linen_amount` are visible to host keeper.

#### Scenario: Super admin can edit bookings
- **WHEN** a super_admin views a booking detail
- **THEN** edit, delete, cancel, and restore buttons are visible and functional

#### Scenario: Host keeper views booking list read-only
- **WHEN** a host_keeper views `/admin/prenotazioni`
- **THEN** the `income_amount` column is not rendered in the table

#### Scenario: Host keeper views booking detail read-only
- **WHEN** a host_keeper views a booking detail at `/admin/prenotazioni/{id}`
- **THEN** edit and delete buttons are not visible; `income_amount` is not shown; `cleaning_amount` and `linen_amount` are visible

#### Scenario: Host keeper cannot access PDF import
- **WHEN** a host_keeper navigates to `/admin/prenotazioni/import-pdf`
- **THEN** access is denied (403 or redirect)

