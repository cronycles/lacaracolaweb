## MODIFIED Requirements

### Requirement: Dashboard displays financial summary
The admin dashboard SHALL display financial totals (income, expenses, balance) for the current year using the same actual dated and checked booking movements as accounting. Its global balance SHALL use the same rules without a year filter. Included parking SHALL follow the booking receipt status for parking summaries. Host keeper users SHALL NOT see the accounting/financial section.

#### Scenario: Super admin sees full dashboard
- **WHEN** a super_admin user navigates to `/admin/`
- **THEN** all dashboard sections are visible including accounting/financial widget

#### Scenario: Host keeper sees dashboard without accounting
- **WHEN** a host_keeper user navigates to `/admin/`
- **THEN** dashboard displays bookings, statistics, but the accounting/financial section is hidden

#### Scenario: Included services and parking
- **WHEN** a dated booking receipt includes services and parking and a service is later paid
- **THEN** dashboard income counts the receipt once and its expenses include the dated service payment
