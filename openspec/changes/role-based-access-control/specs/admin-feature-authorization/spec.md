## ADDED Requirements

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
Host keeper users SHALL only view bookings but not create, edit, or delete them. When viewing booking details, host keeper SHALL see only: checkin, checkout, guest name, email, phone, cleaning_amount, linen_amount. Host keeper SHALL NOT see income_amount.

#### Scenario: Host keeper views bookings
- **WHEN** a host_keeper navigates to `/admin/prenotazioni`
- **THEN** bookings are displayed in read-only mode

#### Scenario: Host keeper cannot create booking
- **WHEN** a host_keeper attempts POST to `/admin/prenotazioni` to create a booking
- **THEN** action is denied

#### Scenario: Host keeper cannot see income amount
- **WHEN** a host_keeper views a booking detail at `/admin/prenotazioni/{id}`
- **THEN** the `income_amount` field is hidden or not shown

#### Scenario: Host keeper cannot access PDF import
- **WHEN** a host_keeper navigates to `/admin/prenotazioni/import-pdf`
- **THEN** access is denied

### Requirement: Calendar authorization
Host keeper users SHALL be able to view and create manual availability blocks on the calendar but only for `owner` reason type.

#### Scenario: Host keeper views calendar
- **WHEN** a host_keeper navigates to `/admin/calendario`
- **THEN** calendar is displayed

#### Scenario: Host keeper can create personal block
- **WHEN** a host_keeper creates a block with reason `owner`
- **THEN** the block is created and appears on calendar

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
