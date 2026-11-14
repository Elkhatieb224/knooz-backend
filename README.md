# KNOOZ

Laravel 12 panels for Admin, Company, and Center (Blade UI), with a shared operational database and a reserved `mobile/` folder for Flutter.

## Documentation

See **[docs/](docs/)**:

- [CURRENT_STATE.md](docs/CURRENT_STATE.md) — pre-refactor snapshot
- [ARCHITECTURE.md](docs/ARCHITECTURE.md)
- [AUTHENTICATION.md](docs/AUTHENTICATION.md)
- [DATABASE_STRUCTURE.md](docs/DATABASE_STRUCTURE.md)
- [DATA_ISOLATION.md](docs/DATA_ISOLATION.md)
- [DEVELOPMENT_GUIDE.md](docs/DEVELOPMENT_GUIDE.md)
- [REFACTORING_REPORT.md](docs/REFACTORING_REPORT.md)

## Quick start

```bash
composer install
php artisan migrate:fresh --seed
php artisan serve
```

## Panel logins

| Panel | URL | Email | Password |
|-------|-----|-------|----------|
| Admin | [/admin/login](/admin/login) | `admin@knooz.test` | `password` |
| Company | [/company/login](/company/login) | `company@knooz.test` | `password` |
| Center | [/center/login](/center/login) | `center@knooz.test` | `password` |

Each panel has its own private login URL — there is no shared portal.

Guards: `admin`, `company`, `center` (session). Customers/employees stay on `users` + `web` for future mobile APIs.

## Tests

```bash
php artisan test --filter=PanelAuthIsolation
```
