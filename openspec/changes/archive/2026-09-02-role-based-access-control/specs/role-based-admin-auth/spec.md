## ADDED Requirements

### Requirement: Role and permission data model
The system SHALL define and persist roles (super_admin, host_keeper, etc.) and permissions (view_bookings, edit_pricing, etc.) in the database. Users SHALL be assignable to a role and MAY have per-user permission overrides.

#### Scenario: User with role and inherited permissions
- **WHEN** a user is assigned the `host_keeper` role
- **THEN** the user inherits all permissions granted to `host_keeper` (e.g., `view_bookings`, `view_people`)

#### Scenario: User with role and per-user override
- **WHEN** a user has role `host_keeper` and a specific permission override is set (e.g., `edit_pricing`)
- **THEN** the user has the override permission in addition to their role permissions

### Requirement: Super admin role
The system SHALL define a `super_admin` role that grants all permissions. The super admin MAY be assigned to any user and SHALL have unrestricted access to all admin features.

#### Scenario: Super admin has all permissions
- **WHEN** a user has the `super_admin` role
- **THEN** the user can perform all admin actions (create/edit/delete bookings, manage pricing, etc.)

### Requirement: Host keeper role
The system SHALL define a `host_keeper` role. Host keeper is a **viewer only** role: can see but cannot create, modify, or delete anything. Permitted features:
- `view_dashboard` (accounting widget excluded, only booking stats)
- `view_calendar` (read-only, cannot create or delete blocks)
- `view_bookings` (read-only; field `income_amount` is hidden everywhere — list and detail)
- `view_people` (read-only)

#### Scenario: Host keeper accesses allowed features
- **WHEN** a user with `host_keeper` role logs in and navigates to `/admin/calendario`
- **THEN** the calendar page loads successfully in read-only mode (no block creation UI visible)

#### Scenario: Host keeper cannot access restricted features
- **WHEN** a user with `host_keeper` role attempts to navigate to `/admin/prezzi`
- **THEN** the page is not accessible (403 or redirect to dashboard)

### Requirement: User permission helpers
The User model SHALL provide methods: `isSuperAdmin(): bool` and `hasPermission(string $permission): bool`. These methods SHALL NOT override Laravel's built-in `can()` method. `hasPermission()` checks role permissions + per-user overrides; if user is super_admin it always returns `true`. The permission `manage_users` is non-delegable: it can never be granted via per-user override, only through the `super_admin` role.

#### Scenario: User permission check with role
- **WHEN** `auth()->user()->hasPermission('view_bookings')` is called on a host_keeper user
- **THEN** the method returns `true`

#### Scenario: User permission check with override
- **WHEN** a super_admin has assigned the host_keeper a specific override permission (e.g., `manage_bookings`)
- **THEN** `auth()->user()->hasPermission('manage_bookings')` returns `true` for that user only

#### Scenario: manage_users is non-delegable
- **WHEN** a super_admin attempts to add `manage_users` as a per-user override for a host_keeper
- **THEN** the system silently ignores or blocks the override; `hasPermission('manage_users')` still returns `false`

### Requirement: Initial data seeding
The system SHALL seed initial roles and permissions on database setup. Super admin role SHALL include all permissions. Host keeper role SHALL include only `view_bookings`, `view_people`, `view_calendar`. The existing user `cronycles@gmail.com` SHALL be automatically assigned the `super_admin` role during seeding.

#### Scenario: Initial role seeding
- **WHEN** migrations and seeders run for the first time
- **THEN** `super_admin` and `host_keeper` roles exist in the database with correct permission assignments

#### Scenario: Existing super admin user migration
- **WHEN** existing users (cronycles@gmail.com) are migrated from no-role to role-based system
- **THEN** cronycles is automatically assigned the `super_admin` role
