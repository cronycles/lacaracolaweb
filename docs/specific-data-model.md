# Data Model (Agent-First)

Data model for La Caracola (single-property rental management).

**Database type:** MySQL (prod) / SQLite (test)  
**Primary keys:** auto-increment on operational tables; `settings.key` is a string primary key.  
**Soft deletes:** enabled on `people` and `bookings`.

## Quick map

- Availability: `bookings` + `availability_blocks`
- Pricing: `pricing_rules` + `stay_discount_rules`
- Runtime DB settings: only `booking_mode`, `booking_external_url`
- Config source of truth: `config/apartment.php`

---

## Entities

### 1. **users**

Admin accounts for the apartment management system. Supports multiple users with different roles.

| Field        | Type         | Notes                                     |
| ------------ | ------------ | ----------------------------------------- |
| `id`         | BIGINT PK    | Auto-increment                            |
| `role_id`    | BIGINT FK    | References `roles.id` (nullable, SET NULL) |
| `name`       | VARCHAR(255) | Admin display name                        |
| `email`      | VARCHAR(255) | Unique, used for login                    |
| `password`   | VARCHAR(255) | Hashed (Laravel bcrypt)                   |
| `created_at` | TIMESTAMP    |                                           |
| `updated_at` | TIMESTAMP    |                                           |

**Notes:**

- Multiple admin users supported with role-based access control
- `role_id` is nullable: a user without a role has no permissions (no access beyond login)
- Password change available in admin panel (`/admin/impostazioni/sicurezza`)
- Super admin created via `RoleSeeder` (assigned to `cronycles@gmail.com`)

**Relations:**

- N → 1 `roles` (user belongs to one role)
- N ↔ N `permissions` via `user_permissions` (per-user overrides)

---

### 2. **people**

Guest/contact database. Records are created during booking requests or manually in admin panel.

| Field                      | Type         | Notes                                               |
| -------------------------- | ------------ | --------------------------------------------------- |
| `id`                       | BIGINT PK    | Auto-increment                                      |
| `first_name`               | VARCHAR(255) | Guest first name                                    |
| `last_name`                | VARCHAR(255) | Guest last name                                     |
| `email`                    | VARCHAR(255) | Email (nullable, unique)                            |
| `phone`                    | VARCHAR(30)  | Contact phone (nullable)                            |
| `birth_date`               | DATE         | Birth date (nullable)                               |
| `country_code`             | VARCHAR(3)   | ISO 3166-1 alpha-2/3 code (nullable)                |
| `document_type`            | VARCHAR(30)  | passport, id_card, driving_license, etc. (nullable) |
| `document_number`          | VARCHAR(60)  | Document ID number (nullable)                       |
| `newsletter_subscribed`    | BOOLEAN      | Default: false                                      |
| `newsletter_subscribed_at` | TIMESTAMP    | Subscription timestamp (nullable)                   |
| `created_at`               | TIMESTAMP    |                                                     |
| `updated_at`               | TIMESTAMP    |                                                     |
| `deleted_at`               | TIMESTAMP    | Soft delete timestamp (nullable)                    |

**Notes:**

- Soft deletes enabled; historical guest records are preserved
- Used for tracking guest information, contacts, and newsletter subscriptions
- Multiple bookings can reference the same person (returning guests)

**Relations:**

- 1 → N `bookings` (guest can have multiple stays)

---

### 3. **bookings**

Individual stay/reservation records linked to a primary guest.

| Field          | Type             | Notes                                              |
| -------------- | ---------------- | -------------------------------------------------- |
| `id`           | BIGINT PK        | Auto-increment                                     |
| `person_id`    | BIGINT FK        | References `people.id` (RESTRICT on delete)        |
| `checkin`      | DATE             | Check-in date (inclusive)                          |
| `checkout`     | DATE             | Check-out date (exclusive)                         |
| `adults`       | UNSIGNED TINYINT | Number of adult guests (1-6)                       |
| `children`     | UNSIGNED TINYINT | Number of children (0-6)                           |
| `babies`       | UNSIGNED TINYINT | Number of babies (0-6)                             |
| `pets`         | UNSIGNED TINYINT | Number of pets (0-4, nullable)                     |
| `source`       | VARCHAR(30)      | Booking source: direct, airbnb, booking, interhome |
| `external_ref` | VARCHAR(60)      | Platform reference ID (nullable)                   |
| `notes`        | TEXT             | Internal notes (nullable)                          |
| `income_amount`| DECIMAL(8,2)     | Incasso ricevuto dalla prenotazione (nullable)     |
| `income_paid`  | BOOLEAN          | Incasso marcato come pagato (default: false)       |
| `income_paid_at` | DATE           | Data di imputazione contabile dell'incasso (nullable, default: checkout) |
| `cleaning_amount` | DECIMAL(8,2)  | Costo pulizie associato alla prenotazione (nullable) |
| `cleaning_paid` | BOOLEAN         | Pulizie marcate come pagate (default: false)       |
| `linen_amount` | DECIMAL(8,2)     | Costo biancheria associato alla prenotazione (nullable) |
| `linen_paid`   | BOOLEAN          | Biancheria marcata come pagata (default: false)    |
| `parking_amount` | DECIMAL(8,2)   | Costo posto auto (ingresso) associato alla prenotazione (nullable, default 10€/notte) |
| `parking_paid` | BOOLEAN          | Posto auto marcato come incassato (default: false) |
| `parking_paid_at` | DATE          | Data di imputazione contabile del parcheggio (nullable, default: checkout) |
| `services_paid_at` | DATE         | Data di imputazione contabile di pulizie+biancheria (nullable, default: checkout) |
| `created_at`   | TIMESTAMP        |                                                    |
| `updated_at`   | TIMESTAMP        |                                                    |
| `canceled_at`  | TIMESTAMP        | Cancellation marker (nullable, indexed)            |
| `deleted_at`   | TIMESTAMP        | Soft delete timestamp (nullable)                   |

**Notes:**

- Soft deletes enabled for historical tracking
- `canceled_at` marks a booking as "canceled" without hard deletion (reversible)
- When `canceled_at IS NULL`, the booking is active; dates are blocked by its associated `availability_block`
- When `canceled_at IS NOT NULL`, the booking is inactive; the associated `availability_block` remains but is ignored in availability queries
- Availability blocks linked to canceled bookings can be safely deleted when the booking is fully deleted
- Indexed on `(checkin, checkout)` for efficient range queries
- `person_id` uses RESTRICT (not CASCADE) to prevent accidental deletion of bookings if a person is deleted

**Relations:**

- N → 1 `people` (primary guest)
- 1 → 1 `availability_blocks` (optional, via `booking_id`)

---

### 4. **financial_entries**

Financial records for extra money movements not linked directly to a booking.

| Field        | Type              | Notes                                                      |
| ------------ | ----------------- | ---------------------------------------------------------- |
| `id`         | BIGINT PK         | Auto-increment                                             |
| `type`       | ENUM('income','expense') | Ingresso o uscita                                         |
| `category`   | VARCHAR(60)       | Categoria libera (es.. manutenzione, utenze, altro)         |
| `description`| TEXT              | Descrizione opzionale                                       |
| `amount`     | DECIMAL(8,2)      | Importo in EUR                                              |
| `entry_date` | DATE              | Data dell'operazione                                        |
| `created_at` | TIMESTAMP         |                                                            |
| `updated_at` | TIMESTAMP         |                                                            |

**Notes:**

- Usata per tracciare ingressi/uscite extra non direttamente legate a una prenotazione.
- `type` determina se la voce contribuisce agli ingressi o alle uscite.
- Viene inclusa nel calcolo dei totali finanziari nella dashboard.

**Relations:**

- None (standalone ledger entries)

---

### 5. **availability_blocks**

Explicit date ranges when the apartment is unavailable (booked, maintenance, or manual owner block).

| Field        | Type        | Notes                                        |
| ------------ | ----------- | -------------------------------------------- |
| `id`         | BIGINT PK   | Auto-increment                               |
| `start_date` | DATE        | Block start date (inclusive)                 |
| `end_date`   | DATE        | Block end date (exclusive)                   |
| `reason`     | VARCHAR(30) | booked, owner, maintenance                   |
| `booking_id` | BIGINT FK   | References `bookings.id` (nullable, CASCADE) |
| `notes`      | TEXT        | Reason details (nullable)                    |
| `created_at` | TIMESTAMP   |                                              |
| `updated_at` | TIMESTAMP   |                                              |

**Notes:**

- Blocks created automatically when bookings are made or imported
- Manual blocks (reason: owner) are created by admin directly
- Indexed on `(start_date, end_date)` for range queries
- `booking_id` is nullable: blocks with `booking_id IS NULL` are manual owner blocks
- When a booking is hard-deleted, its associated block is also deleted (CASCADE)
- When a booking is marked canceled (`canceled_at != null`), the block is NOT deleted, but availability queries exclude the canceled booking's dates

**Relations:**

- N → 1 `bookings` (optional, can be created for manual blocks without booking)

---

### 6. **pricing_rules**

Recurring annual pricing rules defining price per night for specific date ranges.

Dates are stored as **month/day pairs (no year)** — rules repeat annually.

| Field             | Type              | Notes                                      |
| ----------------- | ----------------- | ------------------------------------------ |
| `id`              | BIGINT PK         | Auto-increment                             |
| `start_month`     | UNSIGNED TINYINT  | Start month (1-12)                         |
| `start_day`       | UNSIGNED TINYINT  | Start day (1-31)                           |
| `end_month`       | UNSIGNED TINYINT  | End month (1-12)                           |
| `end_day`         | UNSIGNED TINYINT  | End day (1-31)                             |
| `price_per_night` | UNSIGNED SMALLINT | Price in EUR cents (e.g., 10000 = €100.00) |
| `created_at`      | TIMESTAMP         |                                            |
| `updated_at`      | TIMESTAMP         |                                            |

**Notes:**

- Rules are recurring annually (e.g., Jun 01 - Aug 31 repeats every year)
- Multiple rules can coexist; the system selects the most applicable rule for a given date
- Prices are stored as cents (integers) to avoid float precision issues
- When multiple rules overlap, the most specific (narrowest date range) is prioritized
- Rules are set by admin in `/admin/prezzi`
- Bulk adjustment available to apply a fixed EUR increase/decrease to all rules
- Indexed on `(start_month, start_day, end_month, end_day)` for efficient range lookups

**Relations:**

- None (standalone rules)

---

### 7. **stay_discount_rules**

Tiered discounts applied to stay cost (not cleaning fee) based on number of nights.

| Field              | Type             | Notes                                                |
| ------------------ | ---------------- | ---------------------------------------------------- |
| `id`               | BIGINT PK        | Auto-increment                                       |
| `min_nights`       | UNSIGNED TINYINT | Minimum nights to qualify (e.g., 4)                  |
| `max_nights`       | UNSIGNED TINYINT | Maximum nights for rule (nullable; NULL = unlimited) |
| `discount_percent` | UNSIGNED TINYINT | Discount percentage (0-100, e.g., 10 = 10% off)      |
| `is_active`        | BOOLEAN          | Toggle rule on/off (default: true)                   |
| `priority`         | UNSIGNED TINYINT | Application order (lower = higher priority)          |
| `created_at`       | TIMESTAMP        |                                                      |
| `updated_at`       | TIMESTAMP        |                                                      |

**Notes:**

- Example: "4-6 nights: 10%", "7+ nights: 15%"
- Discounts apply only to the base stay cost, NOT to cleaning fee
- Multiple rules can match a given night count; only the highest-priority active rule is applied
- If `max_nights IS NULL`, the rule applies to all stays >= `min_nights`
- Rule scope: selected in admin panel `/admin/sconti-soggiorno`

**Relations:**

- None (standalone rules)

---

### 8. **settings**

Key-value store for dynamic runtime configuration. Persists admin-level preferences.

| Field   | Type            | Notes                                             |
| ------- | --------------- | ------------------------------------------------- |
| `key`   | VARCHAR(255) PK | Setting name (e.g., 'booking_mode')               |
| `value` | TEXT            | Setting value (stored as string; parse as needed) |

**Currently stored keys:**

- `booking_mode` (values: 'form' or 'external') — whether public booking request uses internal form or external link
- `booking_external_url` (values: URL string) — external booking platform URL when `booking_mode = 'external'`

**Notes:**

- No timestamps (`created_at`, `updated_at`)
- Queries use `Setting::get(key, default)`
- **Not used** for `min_nights`, `cleaning_fee`, `hide_price_from` — those remain in `config/apartment.php`
- Updated from `/admin/impostazioni`

**Relations:**

- None (standalone key-value)

---

### 9. **roles**

Predefined admin roles. Not dynamically creatable via UI — managed via seeders.

| Field         | Type         | Notes          |
| ------------- | ------------ | -------------- |
| `id`          | BIGINT PK    | Auto-increment |
| `name`        | VARCHAR(255) | Unique slug (e.g., `super_admin`, `host_keeper`) |
| `description` | VARCHAR(255) | Human-readable description (nullable) |
| `created_at`  | TIMESTAMP    |                |
| `updated_at`  | TIMESTAMP    |                |

**Seeded roles:**
- `super_admin`: full access to all features
- `host_owner`: full access except `manage_users` and `import_pdf`
- `host_keeper`: viewer only (calendar, bookings without `income_amount`, guests)

**Relations:**
- 1 → N `users`
- N ↔ N `permissions` via `role_permissions`

---

### 10. **permissions**

Feature-level permission slugs. Defined once in `PermissionSeeder`.

| Field         | Type         | Notes          |
| ------------- | ------------ | -------------- |
| `id`          | BIGINT PK    | Auto-increment |
| `name`        | VARCHAR(255) | Unique slug (e.g., `view_bookings`, `manage_pricing`) |
| `description` | VARCHAR(255) | Human-readable description (nullable) |
| `created_at`  | TIMESTAMP    |                |
| `updated_at`  | TIMESTAMP    |                |

**Defined permissions:** `view_bookings`, `manage_bookings`, `import_pdf`, `view_people`, `manage_people`, `view_calendar`, `manage_calendar`, `view_accounting`, `manage_pricing`, `manage_settings`, `manage_newsletter`, `manage_users`

**Relations:**
- N ↔ N `roles` via `role_permissions`
- N ↔ N `users` via `user_permissions` (per-user overrides)

---

### 11. **role_permissions** *(pivot)*

| Field           | Type      | Notes                     |
| --------------- | --------- | ------------------------- |
| `role_id`       | BIGINT FK | References `roles.id`     |
| `permission_id` | BIGINT FK | References `permissions.id` |

Primary key: `(role_id, permission_id)`

---

### 12. **user_permissions** *(pivot)*

Per-user permission overrides, additive to the user's role permissions. `manage_users` is excluded by application logic (non-delegable).

| Field           | Type      | Notes                     |
| --------------- | --------- | ------------------------- |
| `user_id`       | BIGINT FK | References `users.id`     |
| `permission_id` | BIGINT FK | References `permissions.id` |

Primary key: `(user_id, permission_id)`

---

### 13. **interhome_pdf_import_logs**

Log entries for PDF imports from Interhome platform (one entry per import session).

| Field            | Type              | Notes                               |
| ---------------- | ----------------- | ----------------------------------- |
| `id`             | BIGINT PK         | Auto-increment                      |
| `file_name`      | VARCHAR(255)      | Uploaded PDF filename               |
| `bookings_count` | UNSIGNED SMALLINT | Number of bookings parsed           |
| `imported_count` | UNSIGNED SMALLINT | Number of bookings actually created |
| `errors`         | JSON              | Array of error messages (nullable)  |
| `created_at`     | TIMESTAMP         |                                     |

**Notes:**

- Each import (preview + confirm) creates one log entry
- `errors` is a JSON array documenting any validation/creation failures
- Used for audit trail and debugging import issues
- Bookings imported from Interhome get `source = 'interhome'` and `external_ref = '<interhome-reservation-number>'`

**Relations:**

- None (audit log only)

---

## Relationship Summary

```
users (N) ──→ (1) roles
users (N) ↔↔ (N) permissions  [via user_permissions — per-user overrides]
roles (N) ↔↔ (N) permissions  [via role_permissions]

people (1) ──→ (N) bookings
bookings (N) ──→ (1) people
bookings (1) ──→ (1) availability_blocks (optional)
availability_blocks (N) ──→ (1) bookings (optional)

pricing_rules: standalone (recurring annual rules)
stay_discount_rules: standalone (tiered discount rules)
settings: standalone (key-value configuration)
interhome_pdf_import_logs: standalone (audit log)
```

---

## Key Design Notes

1. **Multi-user admin with RBAC:** multiple admin users with predefined roles (`super_admin`, `host_keeper`). Hybrid model: role permissions + per-user overrides. See `docs/specific-tech-backend-doc.mdc` for authorization patterns.

2. **Cancellation semantics:**
    - `canceled_at != null` means booking is inactive but preserved in history.
    - Its linked `availability_block` remains in DB.
    - Public availability queries ignore canceled bookings.
    - Restore is allowed only without conflicts.

3. **Hard delete semantics:**
    - Physical booking deletion removes the linked `availability_block`.

4. **Recurring pricing only:**
    - `pricing_rules` uses only `start_month`, `start_day`, `end_month`, `end_day`.
    - Legacy `start_date`/`end_date` were removed (do not reintroduce).

5. **Config vs settings precedence:**
    - `config/apartment.php` is the source of truth for booking defaults (`min_nights`, `cleaning_fee`, `hide_price_from`).
    - `settings` persists only `booking_mode` and `booking_external_url`.
