# Center App — Flutter Implementation Report

**Date:** 2026-11-10  
**Package:** `knooz_center` at `flutter/center`  
**Scope:** Center App only (User App & Branch Employees App out of scope)

## Architecture

| Layer | Pattern |
|---|---|
| State | Riverpod (`StateNotifier` for auth, `FutureProvider` for lists) |
| Navigation | go_router + `ShellRoute` bottom nav |
| Network | Dio + `{success,message,data,meta?}` unwrap |
| Storage | SharedPreferences token key `knooz_center_auth_token` |
| Locale | Arabic RTL + Tajawal |

Base URL: `String.fromEnvironment('API_BASE_URL', defaultValue: 'http://127.0.0.1:8000/api/center/v1')`

## Screens delivered

1. Splash → token/`auth/me` → home or login  
2. Login — «تسجيل دخول مركز الخدمة»  
3. Forgot password (email)  
4. Main shell BottomNav: الرئيسية | العمليات | التقارير | المزيد  
5. Home dashboard — greeting, KPIs, bookings accept/reject, active ops, quick actions, FAB  
6. Operations list + filters  
7. Add-operation method sheet (QR / NFC / Manual)  
8. QR scanner (`mobile_scanner`) → lookup → details  
9. NFC scanner (`nfc_manager`) + Manual fallback  
10. Manual plate entry → lookup → details  
11. Operation details — multi-select services, payment mode, notes, start → POST operations  
12. Operation complete — status PATCH + before/after image upload  
13. Reports — summary KPIs; financials gated by `can_view_revenue`  
14. More — customers, complaints, services, profile/settings, logout  
15. Customers list + detail  
16. Complaints list + create  
17. Services availability switches  
18. Notifications list + mark read / mark all  
19. Profile edit + password change via `PUT auth/profile`

## Staff revenue rule

Helper `RevenueVisibility` hides:

- Dashboard `revenue_today_net` KPI  
- Operation amount when appropriate  
- Reports financial KPIs + shows restriction message  

## Explicit non-goals (not implemented)

- User App: vehicles / wallet / shop / tow / fuel / tire_repair / wash / customer QR / membership  
- Branch Employees App emergency roadside-only flows  
- FCM / push notifications  
- Company OTP verification (stub message only)

## Tests

- `test/unit/booking_status_mapper_test.dart`  
- `test/unit/revenue_visibility_test.dart`  
- `test/unit/router_exclusions_test.dart`  
- `test/widget/login_validation_test.dart`

## Known gaps

1. Figma pixel-perfect polish (icons/assets) — Material icons used instead of custom SVG set.  
2. NFC reliability varies by device; Manual fallback is always available.  
3. Complaints create form requires numeric `user_id` (API requirement when no operation).  
4. Booking «بدء العملية» from home routes to plate entry rather than auto-binding booking→lookup (can be improved when booking.vehicle plate is used).  
5. Reset-password deep link UI not shipped (API exists; forgot-password sends email channel).  
6. Image gallery pick optional path not exposed (camera-first via `image_picker`).
