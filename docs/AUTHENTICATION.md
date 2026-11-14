# Authentication

## Guards & providers

Configured in `config/auth.php`:

| Guard | Driver | Provider | Model |
|-------|--------|----------|-------|
| `web` | session | `users` | `App\Models\User` |
| `admin` | session | `admin_users` | `App\Models\AdminUser` |
| `company` | session | `company_users` | `App\Models\CompanyUser` |
| `center` | session | `center_users` | `App\Models\CenterUser` |

Default guard remains `web` for future mobile API customers.

## Login URLs

| Panel | GET | POST | Logout |
|-------|-----|------|--------|
| Admin | `/admin/login` | `/admin/login` | `POST /admin/logout` |
| Company | `/company/login` | `/company/login` | `POST /company/logout` |
| Center | `/center/login` | `/center/login` | `POST /center/logout` |
| Legacy `/` and `/login` | redirect to `/admin/login` when guest | — | `POST /logout` clears all guards |

## Controllers

- `App\Http\Controllers\Admin\Auth\LoginController`
- `App\Http\Controllers\Company\Auth\LoginController`
- `App\Http\Controllers\Center\Auth\LoginController`

Shared validation: `App\Http\Requests\PanelLoginRequest` (`login`, `password`, `remember`).

Login accepts email or phone. After success:

1. Status must be `active`
2. Company users must have `company_id`
3. Center users must have `center_id`
4. Session is regenerated; `last_login_at` is updated
5. Only that guard is logged in/out (CSRF token regenerated on logout)

## Middleware

| Alias | Class | Behavior |
|-------|-------|----------|
| `admin` | `EnsureAdmin` | `Auth::shouldUse('admin')`; redirect to `admin.login` if missing; active check |
| `company` | `EnsureCompany` | `shouldUse('company')`; require `company_id`; 403 otherwise |
| `center` | `EnsureCenter` | `shouldUse('center')`; require `center_id`; 403 otherwise |

Route groups use `auth:admin` + `admin` (and equivalents). Guest routes use `guest:admin` etc.

`bootstrap/app.php` sets `redirectGuestsTo` / `redirectUsersTo` based on URL prefix.

## Seed credentials

Password for all: `password`

| Email | Table |
|-------|-------|
| `admin@knooz.test` | `admin_users` (super_admin) |
| `company@knooz.test` | `company_users` |
| `center@knooz.test` | `center_users` |

Additional admin seeds: `operations@knooz.test`, `accountant@knooz.test`, `support@knooz.test`.

## Blade usage

Prefer explicit guards:

```php
auth('admin')->user()
auth('company')->user()
auth('center')->user()
```

`Auth::shouldUse(...)` in panel middleware also makes `$request->user()` and Gates resolve to the active panel model.
