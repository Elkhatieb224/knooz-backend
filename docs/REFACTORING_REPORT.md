# Refactoring Report — Panel Auth & Data Isolation

Date: 2026-11-10

## Problem

Single `users` table + `web` guard mixed admin, company, center, customers, and employees. One `/login` URL and shared session semantics made isolation organizationally weak.

## Changes delivered

### Database

- New tables: `admin_users`, `company_users`, `center_users`
- `centers.manager_center_user_id` → `center_users`
- `legacy_user_id` on panel tables for safe copy from `users`
- Data migration copies panel rows **without** deleting legacy `users` (preserves booking FKs)

### Auth

- Guards: `admin`, `company`, `center` (+ `web` for customers)
- Independent logins: `/admin/login`, `/company/login`, `/center/login`
- No shared portal; each panel uses its own login URL (`/admin/login`, `/company/login`, `/center/login`)
- Middleware uses `$request->user('{guard}')` + `Auth::shouldUse`

### Models & helpers

- `AdminUser`, `CompanyUser`, `CenterUser`
- `Tenant`, `BelongsToCompany`, `BelongsToCenter`
- `Center::managerCenterUser()`, `Company::companyUsers()`, `Center::centerUsers()`
- Admin `RoleController` manages `AdminUser`
- Admin `UserController` still manages customer/employee `User` records

### UI

- Separate login Blade views (Arabic titles)
- Logout posts to `admin.logout` / `company.logout` / `center.logout`
- Layouts/headers/sidebars use explicit guards

### Seeding

- Panel accounts only in new tables (`admin@`, `company@`, `center@`)
- Customers/employees remain on `users`
- `manager_id` left null; `manager_center_user_id` set

### Docs & tests

- Full docs under `docs/`
- `PanelAuthIsolationTest` for cross-guard and tenant isolation

## Intentionally not done

- Moving controllers into a Modules/ package (too breaking for Blade UI)
- Changing operational FKs (`bookings.user_id`, etc.) away from `users`
- Policies for every resource (abort_unless + scopes first)

## Verification checklist

```bash
php artisan migrate:fresh --seed
php artisan test --filter=PanelAuthIsolation
php artisan route:list --path=login
```

Expected logins work with password `password` on the three panel URLs.
