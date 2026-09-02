## Why

Currently, all authenticated admin users have full access to all admin features (bookings, pricing, settings, accounting, newsletter). We need a granular role-based authorization system to:
- Allow multiple admin users with different permission levels (e.g., super admin vs. host keeper)
- Implement least-privilege access: restrict users to only the features they need
- Provide a management UI for the super admin to assign roles and permissions

This is critical for onboarding non-owner users (e.g., property maintenance staff) who need partial access without compromising account security or data integrity.

## What Changes

- **Database**: Add three new tables (`roles`, `permissions`, `role_permissions`, `user_permissions`) to support role and permission assignment
- **Authentication**: Users now have roles + optional per-user permission overrides
- **Authorization**:
  - Super admin (`cronycles@gmail.com`): full access to all features
  - Host keeper (new): **viewer only** — can see dashboard (no accounting widget), booking list/detail (no `income_amount`), guest list, calendar. Cannot create, edit, or delete anything. Cannot access pricing, sconti-soggiorno, settings, accounting, newsletter, PDF import, user management.
  - Infrastructure ready for future roles
- **UI & Admin**:
  - Dashboard conditionally hides sections based on permissions (e.g., accounting widget hidden for host keeper)
  - New admin page `/admin/utenti` to manage users: create users (super admin sets password directly), assign roles, manage per-user permission overrides
- **Policy & Authorization**:
  - `RequirePermission` middleware applied on route groups in `routes/admin.php` — no controller changes needed for access control
  - View conditionals with `auth()->user()->hasPermission()` and `auth()->user()->isSuperAdmin()` in Blade templates
  - Laravel's built-in `can()` is not overridden

**Note**: Roles are role-based + permission-based (hybrid). This allows flexibility—role predefined permissions + per-user overrides for edge cases.

## Capabilities

### New Capabilities
- `role-based-admin-auth`: Defines the role and permission data model, user-role association, and authorization logic
- `role-management-ui`: Admin interface to view, create, assign, and modify user roles and permissions at `/admin/utenti`
- `admin-feature-authorization`: Middleware and controller checks to enforce role-based access on admin routes and views

### Modified Capabilities
- `admin-dashboard`: Conditionally render financial/accounting sections based on user permissions
- `booking-management`: Restrict booking creation/editing/deletion and PDF import based on user role
- `admin-layout`: Navigation conditionally shows/hides links based on user permissions

## Impact

- **Code**:
  - `app/Models/User.php`: Add role relationships and permission helpers
  - `app/Models/Role.php`, `Permission.php`: New models
  - `app/Http/Controllers/Admin/`: All admin controllers check permissions
  - `app/Http/Middleware/`: New middleware for authorization checks
  - `routes/admin.php`: Add user management routes, optional middleware
  - `resources/views/admin/`: Conditional rendering, permission-aware navigation

- **Database**: 4 new migrations (roles, permissions, role_permissions, user_permissions) + 1 migration to add `role_id` to users. ~11 permissions seeded.

- **Testing**: Authorization tests for each role-feature pair

- **Documentation**: Update admin workflow docs to explain role management

- **Breaking changes**: None. Existing single-admin setup migrates automatically (cronycles@gmail.com becomes super_admin)
