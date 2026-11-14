# Database Structure

## Auth tables (panels)

### `admin_users`

| Column | Notes |
|--------|-------|
| id | PK |
| name, email (unique), phone (nullable unique) | |
| password | bcrypt hash |
| role | `super_admin\|operations_manager\|accountant\|support` |
| status | default `active` |
| theme | default `light` |
| avatar, last_login_at, remember_token | |
| legacy_user_id | maps old `users.id` after data migration |
| timestamps, softDeletes | |

### `company_users`

Same profile fields as above, plus:

- `company_id` → `companies` `cascadeOnDelete`
- no role column (company panel account)

### `center_users`

Same profile fields, plus:

- `center_id` → `centers` `cascadeOnDelete`

### `centers.manager_center_user_id`

Nullable FK → `center_users` `nullOnDelete`. Preferred over legacy `manager_id` → `users`.

## Operational `users` table

Kept for:

- Individual customers (`customer_individual`)
- Company contact / pivot rows (`customer_company`) when needed for FKs
- Branch / emergency employees
- Booking / operation / wallet / complaint FKs (`user_id`, `employee_id`, etc.)

Panel logins **must not** rely on this table.

## Migrations

| File | Purpose |
|------|---------|
| `2024_01_01_000220_create_panel_auth_tables.php` | Create panel tables + `manager_center_user_id` |
| `2024_01_01_000230_migrate_panel_users_from_users.php` | Copy existing panel rows from `users` without rehashing passwords; does **not** delete legacy rows |

Password copy uses Query Builder (`DB::table()->insert`) so Eloquent `hashed` casts never double-hash.

## Shared domain (unchanged core)

`companies`, `centers`, `services`, `vehicles`, `bookings`, `operations`, `payments`, `wallets`, `invoices`, `complaints`, `offers`, `packages`, `subscriptions`, `settings`, `notifications`.

Notifications morph to `AdminUser` (and any Notifiable) via `notifiable` morph columns.
