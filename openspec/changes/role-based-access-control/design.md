## Context

Currently:
- Single super admin account (`cronycles@gmail.com`) with full access to all admin features
- No role/permission system; all authenticated users have identical access
- Admin controllers have no authorization checks—only auth middleware on the admin route group
- Future requirement: onboard additional users (e.g., house keeper, maintenance staff) with restricted access

Required behavior:
- **Super admin** (cronycles): see/edit everything
- **Host keeper**: **viewer only** — can see dashboard (no accounting widget), booking list/detail (no `income_amount`), guest list (read-only), calendar (read-only). Cannot create, edit, or delete anything. Cannot access pricing, sconti-soggiorno, settings, accounting, newsletter, PDF import, user management.
- **Dashboard**: conditional widgets based on user permissions
- **UI**: sections hidden/disabled per permissions; forbidden routes return 403 (not just hidden in nav)
- **Management**: super admin UI at `/admin/utenti` to create users, assign roles, manage per-user permission overrides

## Goals / Non-Goals

**Goals:**
- Enable role-based access control (RBAC) on admin features with hybrid model: predefined roles + per-user overrides
- Restrict non-admin users from sensitive areas (pricing, accounting, settings, import)
- Provide super admin UI to create/delete users and manage roles + per-user permission overrides
- No breaking changes to existing auth or routing
- Implement least-privilege: default deny, explicit allow per role
- Keep controllers clean: authorization lives in route middleware and view conditionals, not in controller bodies

**Non-Goals:**
- Multi-tenant support or advanced audit logs (future if needed)
- Public-facing role assignment (only super admin can assign)
- Real-time permission caching or distributed permission checking (single app, local enough)
- Dynamic role creation in UI (predefined roles only: super_admin, host_keeper, future-proof for more)
- Email invitation on user creation (super admin sets password directly and communicates it manually)

## Decisions

### 1. **Data Model: Separate Tables vs. User Column**
**Decision**: Separate tables (`roles`, `permissions`, `role_permissions`, `user_permissions`)

**Rationale**:
- Supports hybrid model: roles + per-user overrides
- Scalable for future features (audit logs, permission history)
- Normalized: avoid data duplication
- Clear separation of concerns: roles are system-level, user permissions are instance-level

**Alternatives Considered**:
- ✗ Single ENUM column on `users` (brittle, no overrides, no scalability)
- ✗ JSON permissions on `users` (works, but denormalizes and complicates querying)

### 2. **Authorization Approach: Middleware vs. Gates vs. Controller Checks**
**Decision**: Middleware-first approach:
- **Auth-level**: Existing `auth` middleware on the whole admin group
- **Feature-level**: `RequirePermission` middleware applied per route group in `routes/admin.php`
- **View-level**: Conditional rendering with `auth()->user()->hasPermission()` in Blade templates

**Rationale**:
- Route groups with permission middleware keep authorization in one place (routes file), not scattered across controllers — DRY
- No controller changes needed for access control: controllers stay thin and focused on business logic
- Template conditionals for UX (hide buttons/links for forbidden actions)
- Avoids authorization bloat in policies or gates (not needed for single-app, non-multi-tenant context)

**Alternatives Considered**:
- ✗ Full Laravel policies (overkill, adds abstraction layer; better for larger multi-resource systems)
- ✗ Controller early returns (pollutes controllers with authorization logic, less DRY)

### 3. **Hybrid Role + Permission Override Model**
**Decision**: Users have a primary role (e.g., "host_keeper") + optional per-user permission overrides in `user_permissions` table.

**Rationale**:
- Roles define standard permission sets (80% use case)
- Per-user overrides handle exceptions without creating new roles (e.g., if host keeper needs one extra permission)
- Flexible and maintainable

**Alternatives Considered**:
- ✗ Only roles, no overrides (too rigid, forces new role creation for edge cases)
- ✗ Only fine-grained permissions, no roles (too complex, user assigns 50+ permissions per user)

### 4. **Predefined Roles vs. Dynamic Role Creation**
**Decision**: Predefined roles in seeder:
- `super_admin`: all permissions
- `host_keeper`: restricted set (calendar, bookings read, guest read, etc.)
- Structure ready for future roles (e.g., `maintenance_worker`, `accountant`)

Roles are **not dynamically creatable** via UI (prevents security misconfiguration).

**Rationale**:
- Roles are system-level decisions, not user-managed
- Reduces complexity; UI manages only user-to-role assignment, not role creation

### 5. **Permission Granularity**
**Decision**: Permissions are **feature-level** with view/manage split where needed. Final list (~11 permissions):
- `view_bookings`, `manage_bookings`
- `view_people`, `manage_people`
- `view_calendar`, `manage_calendar`
- `view_accounting`
- `manage_pricing`
- `manage_settings`
- `manage_newsletter`
- `manage_users` (non-delegable: only `isSuperAdmin()` can grant it, never via per-user override)

**Rationale**:
- `view_*` / `manage_*` split cleanly separates read vs. write where the distinction matters
- `manage_users` is non-delegable by design to prevent privilege escalation
- ~11 permissions avoids explosion while remaining explicit

### 6. **Dashboard Conditional Rendering**
**Decision**: Host keeper sees dashboard but without accounting/financial widgets. Use Blade `@if` directives to conditionally include template sections.

**Rationale**:
- Cleaner UX than 403 redirect
- User knows the page exists but they don't have access to that part
- Single template, condition-aware

## Risks / Trade-offs

| Risk | Mitigation |
| --- | --- |
| **Permission misconfiguration**: Super admin assigns too many permissions to host keeper by mistake | UI shows permission descriptions; super admin must explicitly opt-in per permission. Consider locking host_keeper role in code after initial setup. |
| **Scalability**: If 50+ permissions needed in future | Refactor to permission groups or hierarchical permissions. Current flat structure works for 2-5 roles + ~20 permissions. |
| **Migration**: Moving cronycles to super_admin role on existing DB | Seeder creates role & migration assigns it. Handles gracefully: if user has no role, treat as super_admin (backward compatible). |
| **API exposure**: If future API built, routes must also check permissions | Design separates auth checks so API middleware can reuse same logic (via service/gate). Plan ahead. |

## Migration Plan

1. **Create migrations** (in order):
   - `create_roles_table.php`
   - `create_permissions_table.php`
   - `create_role_permissions_table.php`
   - `create_user_permissions_table.php`
   - `add_role_id_to_users_table.php` (nullable, backward compatible)

2. **Create models**: `Role.php`, `Permission.php`

3. **Update models**: `User.php` add role & permission relationships

4. **Seed initial data**:
   - Create permissions (20-ish records)
   - Create roles: `super_admin`, `host_keeper`
   - Assign permissions to roles
   - Assign `super_admin` role to existing users (or single user `cronycles`)

5. **Implement authorization service**: `AuthorizationService` or traits for `User` model with methods like `can()`, `hasPermission()`, `hasRole()`

6. **Update admin controllers**: Add early-return permission checks in action methods

7. **Update Blade templates**: Add `@if (auth()->user()->can(...))` directives

8. **Create management UI**: `/admin/utenti` controller, view, form

9. **Test**: Verify each user type (super_admin, host_keeper) can/cannot access expected pages

10. **Deploy**: Standard Laravel migration + seeders. Backward compatible: cronycles automatically granted super_admin on first run.

## Open Questions

1. **Email notification on user creation/role change?** Current spec silent; skip for MVP, consider for future.
2. **Audit log for permission changes?** Not in scope. Consider storing user_permissions change history if compliance needed.
3. **Session invalidation on permission change?** Current user continues with old permissions until next login. Accept or invalidate active sessions?
4. **Host keeper password change?** Should they access `/admin/impostazioni/sicurezza`? Current spec: only own password or super admin controls?
