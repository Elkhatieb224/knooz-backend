# KNOOZ Architecture

## Overview

KNOOZ is a Laravel 12 monolith at the repository root. Blade panels serve Admin, Company, and Center. Operational data (customers, bookings, vehicles, payments) lives in a shared database with strict tenant scoping. A `mobile/` folder is reserved for a future Flutter client.

## High-level layout

| Area | Path | Role |
|------|------|------|
| Admin panel | `/admin` | Operations, finance, settings, roles |
| Company panel | `/company` | Fleet, invoices, subscriptions |
| Center panel | `/center` | Bookings & operations for one center |
| Auth | Dedicated URLs only | `/admin/login`, `/company/login`, `/center/login` |
| Mobile app | `mobile/` | Flutter (placeholder) |
| Docs | `docs/` | Architecture & development notes |

## Auth boundaries

- **Admin** → `admin_users` + guard `admin`
- **Company** → `company_users` + guard `company`
- **Center** → `center_users` + guard `center`
- **Customers / employees** → `users` + guard `web` (mobile / operational FKs)

Panel sessions use separate guard keys in the same Laravel session cookie. Logging out of one panel does not wipe other guards.

## Application layers

```
routes/{admin,company,center,web}.php
  → Http/Controllers/{Admin,Company,Center,Auth}
  → Models + Support/Tenant + Concerns (scopes)
  → Services/* (dashboard aggregations, etc.)
  → Blade views under resources/views/{admin,company,center}
```

Controllers stay namespaced under `Admin|Company|Center`. A full Modules/ move is intentionally deferred to avoid breaking Blade UI.

## Shared operational domain

One database holds:

- Companies, centers, services, vehicles
- Bookings, operations, payments, wallets, invoices
- Offers, packages, subscriptions, complaints, settings

Isolation is enforced by:

1. Separate panel credentials (cannot authenticate as another panel type)
2. Middleware (`EnsureAdmin` / `EnsureCompany` / `EnsureCenter`)
3. `Tenant::companyId()` / `Tenant::centerId()`
4. Eloquent scopes `forCompany` / `forCenter`
5. `abort_unless` ownership checks in company/center controllers

## Related docs

- [AUTHENTICATION.md](AUTHENTICATION.md)
- [DATABASE_STRUCTURE.md](DATABASE_STRUCTURE.md)
- [DATA_ISOLATION.md](DATA_ISOLATION.md)
- [DEVELOPMENT_GUIDE.md](DEVELOPMENT_GUIDE.md)
- [REFACTORING_REPORT.md](REFACTORING_REPORT.md)
