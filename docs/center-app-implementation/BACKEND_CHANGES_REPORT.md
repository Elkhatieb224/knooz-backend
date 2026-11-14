# Center App Backend Changes Report

**Date:** 2026-11-10  
**Scope:** `/api/center/v1` JSON API for Flutter Center App  
**Constraint:** Additive only — User App `/api/v1`, Center web `/center`, Admin, Company untouched in behavior

---

## Summary

Implemented a full Center mobile API under `/api/center/v1` with Bearer auth via `PanelApiToken` (`panel=center`), tenant scoping from authenticated `CenterUser`, and product decisions D1–D10.

---

## Migrations

`database/migrations/2024_01_01_000270_add_center_app_api_fields.php`

| Table | Change |
|---|---|
| `center_users` | `role` string default `manager` (`manager`\|`staff`) |
| `centers` | `show_revenue` boolean nullable; seeded owned=true, contracted=false |
| `operations` | `created_by_center_user_id` nullable FK → `center_users`; `source` nullable string |
| `complaints` | `center_id`, `operation_id` nullable FKs; `source` nullable string |

---

## Auth & Middleware

- `AuthenticatePanelApiToken` — stores token model + plain token on request (for logout revoke); prefers token `panel` column
- `EnsureCenterApi` (`center.api`) — requires `panel=center`, active `CenterUser` with `center_id`, active center; sets `Auth::guard('center')` for Tenant reuse
- Registered in `bootstrap/app.php`
- Password reset broker: existing `center_users` + `CenterResetPasswordNotification` (email with token)

---

## Endpoints (`/api/center/v1`)

### Auth
| Method | Path | Notes |
|---|---|---|
| POST | `auth/login` | `{login, password}` → Bearer token |
| POST | `auth/logout` | Revokes current PanelApiToken |
| GET | `auth/me` | User + center |
| PUT | `auth/profile` | name/email/phone/theme/password |
| POST | `auth/forgot-password` | Email reset link/token |
| POST | `auth/reset-password` | `{email, token, password}` |

### Core
| Method | Path |
|---|---|
| GET | `dashboard` |
| GET/PATCH | `bookings`, `bookings/{id}` |
| GET/POST/PATCH | `operations`, `operations/lookup`, `operations/{id}`, `operations/{id}/images` |
| GET | `reports/summary` |
| GET | `customers`, `customers/{userId}` |
| GET/POST | `complaints` |
| GET/POST/DELETE | `notifications` (+ unread-count, read, read-all, open) |
| GET/PATCH | `services`, `services/{serviceId}` (`is_available` only) |

**Scoping rule:** `center_id` always from auth user — never from request body.

### Admin UI
- `resources/views/admin/centers/_form.blade.php` — مفتاح **إظهار الأرباح في تطبيق المركز** (`show_revenue`) ليتمكن الإدارة من التحكم بوضوح للمتعاقد/المملوك.

---

## Product rules implemented

| Decision | Implementation |
|---|---|
| D1 Separate API | `/api/center/v1` vs `/api/v1` |
| D2 Roles | `CenterUserRole` manager/staff; `created_by_center_user_id` |
| D3 Booking accept/reject | accept→`in_progress`, reject→`cancelled` (+ notes required) |
| D4 Multi-service | Multiple `operations` rows per create |
| D5 Company OTP | `requires_verification=false`; stub message for company vehicles |
| D6 Service toggle | PATCH `is_available` only |
| D7 Complaints | Extended columns + center-scoped list/create |
| D8 Staff revenue | Hidden in dashboard/reports for staff |
| D9 Forgot password | Email via Password broker |
| D10 NFC | Lookup `method=nfc\|qr\|plate` |
| Revenue net | `amount - app_profit` (or contract_price fallback) |
| Contracted hide | `Center::showsRevenue()` — flag or type default |
| Walk-in | Create operation without booking always allowed |

---

## Code layout

| Area | Path |
|---|---|
| Routes | `routes/api/center/v1.php` (required from `routes/api.php`) |
| Controllers | `app/Http/Controllers/Api/Center/V1/*` |
| Services | `app/Services/Center/{CenterAuth,OperationLookup,OperationCreate,Report,Dashboard}Service` |
| Requests | `app/Http/Requests/Api/Center/*` |
| Resources | `app/Http/Resources/Center/*` |
| Enum | `app/Enums/CenterUserRole.php` |
| Notification | `app/Notifications/CenterResetPasswordNotification.php` |
| Tests | `tests/Feature/CenterApi/*` |

---

## Payment handling (operation create)

- Amount from `center_service.contract_price` else `service.base_price`
- `cash` → `unpaid`
- `credit` → `credit`
- `subscription` → `subscription` if active sub exists, else `unpaid`
- `wallet` → best-effort debit via `Wallet` + `WalletTransaction` if balance sufficient; otherwise leave `unpaid` (no forced broken debit)

---

## Tests

```bash
php artisan test --filter=CenterApi
```

**Result (2026-11-10):** 12 passed (47 assertions)

Coverage:
- Login success / fail / inactive user / inactive center
- Me + logout token revoke
- IDOR isolation between centers
- Booking accept / reject
- Lookup (plate/qr/nfc) + create + status update
- Staff cannot see revenue
- Contracted hides revenue
- Owned manager sees net revenue

---

## Admin note

`centers.show_revenue` is fillable and validated on `StoreCenterRequest` / `UpdateCenterRequest` (`nullable|boolean`) so Admin can override contracted/owned defaults without a separate migration later.

---

## Blockers / follow-ups (non-blocking for v1)

1. Company verification OTP / FCM — deferred (D5)
2. Admin UI form fields for `show_revenue` / center user `role` may need Blade updates (API/DB ready)
3. Wallet debit is best-effort; richer payment flows can reuse Admin wallet tools later
4. Branch Employees App — out of scope
