# توثيق API تطبيق المستخدم – `/api/v1`

Base URL محلي: `http://127.0.0.1:8000/api/v1`  
Auth: `Authorization: Bearer {token}` (Sanctum personal access token)

## Envelope

نجاح:
```json
{ "success": true, "message": "OK", "data": {}, "meta": {} }
```

فشل:
```json
{ "success": false, "message": "...", "errors": {} }
```

## Auth

| Method | Path | Auth | الوصف |
|---|---|---|---|
| POST | `/auth/register` | لا | تسجيل فردي |
| POST | `/auth/login` | لا | `login` + `password` (هاتف أو بريد) |
| POST | `/auth/forgot-password` | لا | طلب رمز (stub) |
| POST | `/auth/logout` | نعم | إلغاء التوكن الحالي |
| GET | `/auth/me` | نعم | الملف |
| PUT | `/auth/profile` | نعم | تحديث الملف |
| POST | `/auth/change-password` | نعم | تغيير كلمة المرور |

### Register body
`name, email, phone, password, password_confirmation, city?, gender?`

### Login response data
`token`, `token_type`, `user`

## Home & Catalog

| Method | Path | Auth |
|---|---|---|
| GET | `/home?lat=&lng=` | نعم |
| GET | `/services` | لا |
| GET | `/centers?city=&q=` | لا |
| GET | `/centers/{id}` | لا |
| GET | `/offers` | لا |
| GET | `/packages?type=` | لا |

## Vehicles

| Method | Path |
|---|---|
| GET/POST | `/vehicles` |
| GET/PUT/DELETE | `/vehicles/{id}` |
| GET | `/vehicles/{id}/qr` |

## Bookings

| Method | Path |
|---|---|
| GET/POST | `/bookings` |
| GET | `/bookings/{id}` |
| POST | `/bookings/{id}/cancel` |
| GET | `/centers/{id}/slots?date=YYYY-MM-DD` |

Create body: `vehicle_id, center_id, service_id, scheduled_at, notes?, price?`

## Wallet

| Method | Path |
|---|---|
| GET | `/wallet` |
| POST | `/wallet/recharge` (`amount`, `method=bank_transfer|online`, `receipt?`) |
| GET | `/wallet/payments` |

## Emergency

| Method | Path |
|---|---|
| GET/POST | `/emergency` |
| GET | `/emergency/{id}` |

Create body: `kind=tire|fuel|tow`, `lat`, `lng`, `vehicle_id?`, `address?`, `notes?`, `payload?`

## Shop

| Method | Path |
|---|---|
| GET | `/shop/products?category=&brand=&q=` |
| GET | `/shop/products/{id}` |

## Notifications

| Method | Path |
|---|---|
| GET | `/notifications` |
| GET | `/notifications/unread-count` |
| POST | `/notifications/read-all` |
| POST | `/notifications/{id}/read` |
| GET | `/notifications/{id}/open` |
| POST | `/device-tokens` |

## Rate limits
- عام v1: 120/min
- login: 20/min
- register: 10/min
- forgot: 5/min
