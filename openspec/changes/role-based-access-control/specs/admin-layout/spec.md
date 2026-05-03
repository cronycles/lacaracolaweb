## MODIFIED Requirements

### Requirement: Admin navigation sidebar
The admin navigation sidebar SHALL conditionally show/hide links based on user permissions. Users without access to a feature SHALL NOT see the corresponding sidebar link.

#### Scenario: Super admin sees all navigation links
- **WHEN** a super_admin user views the admin area
- **THEN** all navigation links are visible (dashboard, calendar, pricing, bookings, guests, settings, accounting, newsletter, users)

#### Scenario: Host keeper sees limited navigation
- **WHEN** a host_keeper user views the admin area
- **THEN** only permitted navigation links are visible: dashboard, calendar, bookings, guests. Links for pricing, settings, accounting, newsletter, users are hidden.

#### Scenario: Button visibility based on permissions
- **WHEN** a host_keeper views a section they can access
- **THEN** only read-only or view buttons are shown; action buttons (create, edit, delete) are hidden or disabled

#### Scenario: Super admin sees all action buttons
- **WHEN** a super_admin views any admin section
- **THEN** all available action buttons are visible and functional
