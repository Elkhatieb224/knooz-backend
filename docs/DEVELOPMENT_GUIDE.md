# Development Guide

## Setup

```bash
composer install
cp .env.example .env   # if needed
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

Optional assets: `npm install && npm run build` (or `dev`).

## Login locally

| Panel | URL | Email | Password |
|-------|-----|-------|----------|
| Admin | `/admin/login` | `admin@knooz.test` | `password` |
| Company | `/company/login` | `company@knooz.test` | `password` |
| Center | `/center/login` | `center@knooz.test` | `password` |

## Adding a company-scoped endpoint

1. Put the route under `routes/company.php` inside `auth:company` + `company`.
2. Use `Tenant::companyId()` and `Model::query()->forCompany($id)`.
3. On show/update: `abort_unless($model->company_id === Tenant::companyId(), 404)`.
4. Use `auth('company')->user()` in views/controllers.

Same pattern for center with `Tenant::centerId()` / `forCenter`.

## Adding an admin staff user

Insert into `admin_users` (or Admin UI → roles after login as super_admin). Do **not** put panel admins into `users`.

## Password hashing

Eloquent models use the `hashed` cast. Prefer plain strings when creating via Eloquent. When copying existing hashes (migrations), use `DB::table()->insert([... 'password' => $row->password])`.

## Tests

```bash
php artisan test --filter=PanelAuthIsolation
php artisan test
```

Feature tests use SQLite in-memory (`phpunit.xml`).

## Mobile

`mobile/` is reserved for Flutter. Keep Laravel at the repo root; do not nest the API app under `mobile/`.

## Docs map

Start with [CURRENT_STATE.md](CURRENT_STATE.md) (pre-refactor snapshot), then [ARCHITECTURE.md](ARCHITECTURE.md) and [AUTHENTICATION.md](AUTHENTICATION.md).
