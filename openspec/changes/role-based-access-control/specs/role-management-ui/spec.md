## ADDED Requirements

### Requirement: User management page at /admin/utenti
The system SHALL provide an admin page at `/admin/utenti` where super admin users can view all admin users, assign roles, and manage per-user permission overrides. Only super admin users SHALL access this page.

#### Scenario: Super admin views user list
- **WHEN** a super admin navigates to `/admin/utenti`
- **THEN** a list of all admin users is displayed with their current role and email

#### Scenario: Host keeper denied access to user management
- **WHEN** a host_keeper user attempts to navigate to `/admin/utenti`
- **THEN** access is denied (403 or redirect)

### Requirement: Assign role to user
The system SHALL allow super admin to assign a role to a user via a form or dropdown on the user management page. Role assignment SHALL take effect immediately.

#### Scenario: Super admin assigns role
- **WHEN** super admin selects `host_keeper` role for a user and submits the form
- **THEN** the user is assigned the `host_keeper` role and can log in with restricted permissions

### Requirement: Override permissions per user
The system SHALL allow super admin to grant or revoke specific permissions to/from a user, independent of their role. Per-user overrides SHALL be displayed clearly and take precedence over role permissions.

#### Scenario: Super admin grants override permission
- **WHEN** super admin assigns a specific permission (e.g., `edit_pricing`) to a host_keeper user
- **THEN** that user gains the permission and can access that feature even though host_keeper role doesn't normally grant it

#### Scenario: Super admin revokes override permission
- **WHEN** super admin removes a per-user override permission
- **THEN** the user loses that permission immediately (reverts to role-based permissions)

### Requirement: User creation
The system SHALL allow super admin to create new admin users with email and assign them a role. The new user SHALL receive an email with a temporary password or login link.

#### Scenario: Super admin creates new admin user
- **WHEN** super admin submits a form with email `housekeeper@example.com` and role `host_keeper`
- **THEN** a new user is created with that role and the specified email

### Requirement: User deletion
The system SHALL allow super admin to delete admin users. Deletion SHALL be permanent.

#### Scenario: Super admin deletes user
- **WHEN** super admin selects a user and confirms deletion
- **THEN** the user record is deleted and the user can no longer log in
