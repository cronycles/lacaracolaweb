## 1. Database — Migrations & Models

- [x] 1.1 Create migration `create_roles_table.php`: `id, name, description, timestamps`
- [x] 1.2 Create migration `create_permissions_table.php`: `id, name, description, timestamps`
- [x] 1.3 Create migration `create_role_permissions_table.php`: pivot `role_id, permission_id`
- [x] 1.4 Create migration `create_user_permissions_table.php`: pivot `user_id, permission_id` (per-user overrides)
- [x] 1.5 Create migration `add_role_id_to_users_table.php`: nullable `role_id` FK on `users`
- [x] 1.6 Create `Role.php` model with relationships: `permissions()` (BelongsToMany), `users()` (HasMany)
- [x] 1.7 Create `Permission.php` model with relationships: `roles()` (BelongsToMany), `users()` (BelongsToMany)
- [x] 1.8 Update `User.php`: add `role()` relationship, `permissionOverrides()` BelongsToMany, and methods:
  - `isSuperAdmin(): bool` — returns `true` if role is `super_admin`
  - `hasPermission(string $permission): bool` — if `isSuperAdmin()` returns `true`; else checks role permissions + per-user overrides. Note: `manage_users` is non-delegable (only `isSuperAdmin()` can grant it, never via override)

## 2. Seeding — Initial Roles & Permissions

- [x] 2.1 Create `PermissionSeeder.php`: define the following permissions (slug + description):
  - `view_bookings` — lista e dettaglio prenotazioni (read-only, senza income_amount)
  - `manage_bookings` — crea/modifica/elimina/cancella prenotazioni + import PDF
  - `view_people` — lista e dettaglio ospiti (read-only)
  - `manage_people` — crea/modifica/elimina ospiti
  - `view_calendar` — vedi calendario
  - `manage_calendar` — crea/elimina blocchi disponibilità
  - `view_accounting` — accesso contabilità, campo income_amount, widget dashboard
  - `manage_pricing` — prezzi e sconti-soggiorno
  - `manage_settings` — impostazioni generali
  - `manage_newsletter` — newsletter
  - `manage_users` — gestione utenti (non delegabile via override)
- [x] 2.2 Create `RoleSeeder.php`:
  - `super_admin`: tutti i permessi
  - `host_keeper`: solo `view_bookings`, `view_people`, `view_calendar`
- [x] 2.3 Update `DatabaseSeeder.php`: run `PermissionSeeder` → `RoleSeeder` in order
- [x] 2.4 In `RoleSeeder`: assign `super_admin` role to the existing user `cronycles@gmail.com`

## 3. Middleware — Route Authorization

- [x] 3.1 Create `RequirePermission` middleware: accepts permission slug as parameter; calls `auth()->user()->hasPermission($permission)`; on failure, redirect to dashboard with flash error message
- [x] 3.2 Register alias `permission` for the middleware in `bootstrap/app.php`
- [x] 3.3 Reorganize `routes/admin.php` with route groups by permission. Exact grouping:
  - **No extra middleware** (accessible by all authenticated users):
    - `GET /` (dashboard)
    - `GET /calendario`
    - `GET /prenotazioni` (index)
    - `GET /prenotazioni/{id}` (show)
    - `GET /ospiti` (index), `GET /ospiti/{id}` (show), `GET /ospiti/{id}/soggiorni`
    - `GET /impostazioni/sicurezza`, `POST /impostazioni/sicurezza/password`
  - **`permission:manage_calendar`**: `POST /blocchi`, `DELETE /blocchi/{block}`, all `/prenotazioni/blocco/*` routes (show, edit, update, destroy)
  - **`permission:manage_bookings`**: all import-pdf routes, cancel, restore, `POST/PUT/DELETE /prenotazioni*`
  - **`permission:manage_people`**: `POST/PUT/DELETE /ospiti*`
  - **`permission:view_accounting`**: all `/contabilita*` routes
  - **`permission:manage_pricing`**: all `/prezzi*` and `/sconti-soggiorno*` routes
  - **`permission:manage_settings`**: `GET/PUT /impostazioni` (not `/impostazioni/sicurezza`)
  - **`permission:manage_newsletter`**: all `/newsletter*` routes
  - **`permission:manage_users`**: all `/utenti*` routes

## 4. Views — Conditional Rendering

- [x] 4.1 Dashboard: wrap accounting/financial widget in `@if(auth()->user()->hasPermission('view_accounting'))`
- [x] 4.2 Sidebar/nav: wrap each restricted link with its corresponding permission check:
  - `/prezzi`, `/sconti-soggiorno` → `manage_pricing`
  - `/contabilita` → `view_accounting`
  - `/impostazioni` (general) → `manage_settings`
  - `/newsletter` → `manage_newsletter`
  - `/utenti` → `manage_users` (or `isSuperAdmin()`)
- [x] 4.3 Booking list view: wrap `income_amount` column in `@if(auth()->user()->hasPermission('view_accounting'))`
- [x] 4.4 Booking detail view: wrap `income_amount` field in `@if(auth()->user()->hasPermission('view_accounting'))`; wrap edit/delete/cancel/restore buttons in `@if(auth()->user()->hasPermission('manage_bookings'))`; hide "import PDF" button for non `manage_bookings`
- [x] 4.5 Ospiti list/detail: wrap create/edit/delete buttons in `@if(auth()->user()->hasPermission('manage_people'))`
- [x] 4.6 Calendario view: wrap "crea blocco" button/form in `@if(auth()->user()->hasPermission('manage_calendar'))`

## 5. Admin — User Management (`/admin/utenti`)

- [x] 5.1 Create `UserController.php` with actions: `index`, `create`, `store`, `edit`, `update`, `destroy`
- [x] 5.2 Create views:
  - `admin/users/index.blade.php`: table of all admin users (name, email, role, actions)
  - `admin/users/create.blade.php`: form with name, email, password (plain), role dropdown
  - `admin/users/edit.blade.php`: change role dropdown + permission override checkboxes (only permissions where `manage_users` is NOT the permission itself)
- [x] 5.3 Implement `store`: create user with hashed password set by super admin; assign selected role
- [x] 5.4 Implement `update`: update role + sync per-user permission overrides (use `sync()` on the relationship)
- [x] 5.5 Implement `destroy`: delete user; guard against self-deletion (cannot delete own account)

## 6. Feature Tests

- [x] 6.1 `SuperAdminAuthorizationTest`: assert super_admin gets 200 on all admin routes
- [x] 6.2 `HostKeeperAuthorizationTest`: assert host_keeper gets 403/redirect on forbidden routes; assert 200 on allowed routes (dashboard, calendar, prenotazioni index/show, ospiti index/show, sicurezza)
- [x] 6.3 `HostKeeperViewTest`: assert `income_amount` is not present in booking list/show response for host_keeper
- [x] 6.4 `PermissionOverrideTest`: host_keeper with manual override `manage_bookings` can access the bookings creation route

## 7. Documentation

- [x] 7.1 Update `docs/specific-tech-backend-doc.mdc`: add RBAC section with:
  - available roles and their slugs
  - full permission list with slugs and what they protect
  - how to check permissions: `auth()->user()->hasPermission('slug')` or `auth()->user()->isSuperAdmin()`
  - note on non-delegable `manage_users` permission
