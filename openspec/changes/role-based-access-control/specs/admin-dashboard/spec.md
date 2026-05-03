## MODIFIED Requirements

### Requirement: Dashboard displays financial summary
The admin dashboard SHALL display financial totals (income, expenses, balance) for the current year. Host keeper users SHALL NOT see the accounting/financial section.

#### Scenario: Super admin sees full dashboard
- **WHEN** a super_admin user navigates to `/admin/`
- **THEN** all dashboard sections are visible including accounting/financial widget

#### Scenario: Host keeper sees dashboard without accounting
- **WHEN** a host_keeper user navigates to `/admin/`
- **THEN** dashboard displays bookings, statistics, but the accounting/financial section is hidden
