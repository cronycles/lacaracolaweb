## 1. Database Migrations & Models

- [ ] 1.1 Create migration `create_roles_table.php`: id, name, description, timestamps
- [ ] 1.2 Create migration `create_permissions_table.php`: id, name, description, timestamps
- [ ] 1.3 Create migration `create_role_permissions_table.php`: role_id, permission_id (pivot)
- [ ] 1.4 Create migration `create_user_permissions_table.php`: user_id, permission_id (pivot for overrides)
- [ ] 1.5 Create migration `add_role_id_to_users_table.php`: nullable role_id foreign key
- [ ] 1.6 Create `Role.php` model with relationships to permissions and users
- [ ] 1.7 Create `Permission.php` model with relationships to roles and users
- [ ] 1.8 Update `User.php` model: add role relationship, hasMany user_permissions, add permission check methods

## 2. Authorization Service & Traits

- [ ] 2.1 Create `AuthorizationService` or trait `CanManagePermissions` in `User.php` with methods: `hasPermission(string $permission)`, `hasRole(string $role)`, `can(string $permission)`
- [ ] 2.2 Implement permission lookup logic: check user role permissions + per-user overrides
- [ ] 2.3 Add helper function `can_admin(string $permission): bool` for blade templates
- [ ] 2.4 Create `AdminAuthorization` gate in `AppServiceProvider.php` (optional, for consistency with Laravel conventions)

## 3. Create Initial Roles & Permissions Seed Data

- [ ] 3.1 Create `RoleSeeder.php`: define super_admin role with all permissions, host_keeper role with restricted permissions
- [ ] 3.2 Create `PermissionSeeder.php`: define ~20 permissions (view_dashboard, edit_bookings, view_pricing, view_accounting, etc.)
- [ ] 3.3 Add seeders to `DatabaseSeeder.php`
- [ ] 3.4 Create migration task: assign super_admin role to cronycles@gmail.com (via seeder or migration callback)

## 4. Admin Controllers: Authorization Checks

- [ ] 4.1 Update `DashboardController`: add permission check for `view_dashboard` (always allow, but conditionally show financial data)
- [ ] 4.2 Update `CalendarController`: add check for `view_calendar` and `edit_calendar`
- [ ] 4.3 Update `BookingController`: add checks for `view_bookings`, `create_bookings`, `edit_bookings`, `delete_bookings`; hide income_amount if no `view_accounting` permission
- [ ] 4.4 Update `InterhomePdfImportController`: add check for `import_bookings` (host_keeper denied)
- [ ] 4.5 Update `PricingController`: add check for `edit_pricing` (host_keeper denied)
- [ ] 4.6 Update `StayDiscountRuleController`: add check for `edit_stay_discounts` (host_keeper denied)
- [ ] 4.7 Update `PersonController`: add checks for `view_people`, `create_people`, `edit_people`, `delete_people`
- [ ] 4.8 Update `NewsletterController`: add check for `manage_newsletter` (host_keeper denied)
- [ ] 4.9 Update `FinancialEntryController`: add check for `view_accounting` and `edit_accounting` (host_keeper denied)
- [ ] 4.10 Update `SettingsController`: add check for `manage_settings` (host_keeper denied)

## 5. Views: Conditional Rendering

- [ ] 5.1 Update admin dashboard template: conditionally render accounting widget using `@if (auth()->user()->can('view_accounting'))`
- [ ] 5.2 Update admin sidebar/navigation: conditionally show links based on user permissions (pricing, settings, newsletter, accounting, users)
- [ ] 5.3 Update bookings list/detail view: hide income_amount field if user lacks `view_accounting` permission
- [ ] 5.4 Update booking action buttons (create, edit, delete): hide if user lacks corresponding permission
- [ ] 5.5 Update people/guests view: hide edit/delete buttons if user lacks `edit_people` permission
- [ ] 5.6 Update calendar view: show/hide manual block creation based on `edit_calendar` permission

## 6. Admin Users Management Page

- [ ] 6.1 Create `UserController.php` for `/admin/utenti` routes: index, show, edit, update, create, store, destroy
- [ ] 6.2 Add routes to `routes/admin.php` for `/admin/utenti` (protected by super_admin check)
- [ ] 6.3 Create views: `admin/users/index.blade.php` (list users with roles), `edit.blade.php` (assign role + override permissions), `create.blade.php` (create new user)
- [ ] 6.4 Implement form: role dropdown, permission checkboxes for per-user overrides
- [ ] 6.5 Implement user creation: generate temporary password and email invitation link
- [ ] 6.6 Implement user deletion with confirmation

## 7. Database Seeding & Migration

- [ ] 7.1 Run migrations: `php artisan migrate` (creates role, permission, pivot tables)
- [ ] 7.2 Run seeders: `php artisan db:seed` (creates initial roles and permissions, assigns super_admin to cronycles)
- [ ] 7.3 Verify: Check database—cronycles should have role_id = super_admin role

## 8. Testing & Verification

- [ ] 8.1 Test super_admin: can access all admin routes and see all data (no conditionals apply)
- [ ] 8.2 Test host_keeper: can access calendar, bookings (read-only), guests (read-only); denied on pricing, settings, accounting, newsletter
- [ ] 8.3 Test host_keeper booking view: income_amount is hidden, pulizie/biancheria visible
- [ ] 8.4 Test per-user permission override: assign extra permission to host_keeper, verify they can access that feature
- [ ] 8.5 Test dashboard: accounting widget hidden for host_keeper, visible for super_admin
- [ ] 8.6 Test user management page: super_admin can create/edit/delete users; host_keeper cannot access the page
- [ ] 8.7 Test sidebar navigation: links hidden per role (pricing, settings, etc. hidden for host_keeper)

## 9. Documentation & Cleanup

- [ ] 9.1 Update `docs/specific-tech-backend-doc.mdc`: add role-based authorization section (roles available, permission list, how to check permissions)
- [ ] 9.2 Add comments in User model explaining permission check logic
- [ ] 9.3 Update README or admin onboarding docs with user management instructions
- [ ] 9.4 Verify all conventional commits follow project standards
- [ ] 9.5 Code review: check DRY principle, no spaghetti code, proper separation of concerns
