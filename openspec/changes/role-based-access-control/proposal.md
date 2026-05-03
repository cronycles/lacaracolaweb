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
  - Host keeper (new): restricted access—can see dashboard, upcoming bookings, guest data, calendar, but cannot manage pricing, settings, accounting, or import PDFs
  - Infrastructure ready for future roles
- **UI & Admin**:
  - Dashboard conditionally hides sections based on permissions (e.g., accounting widget hidden for host keeper)
  - New admin page `/admin/utenti` to manage users, assign roles, and override permissions
  - Conditional rendering in templates to show/hide restricted sections
- **Policy & Authorization**:
  - Gate/middleware checks on admin routes for feature-level access
  - Early return in controllers for action-level authorization

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

- **Database**: 4 new migrations (roles, permissions, role_permissions, user_permissions)

- **Testing**: Authorization tests for each role-feature pair

- **Documentation**: Update admin workflow docs to explain role management

- **Breaking changes**: None. Existing single-admin setup migrates automatically (cronycles@gmail.com becomes super_admin)
