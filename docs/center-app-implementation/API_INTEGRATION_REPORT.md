# Center App — API Integration Report

**Base:** `/api/center/v1`  
**Auth header:** `Authorization: Bearer {PanelApiToken}`  
**Envelope:** `{ success, message, data, meta? }`

## Screen → Endpoint map

| Screen / Action | Method | Path | Body / Notes |
|---|---|---|---|
| Splash / bootstrap | GET | `auth/me` | Restore session |
| Login | POST | `auth/login` | `{login,password}` → token + user |
| Logout | POST | `auth/logout` | Clears token locally even on network fail |
| Forgot password | POST | `auth/forgot-password` | `{email}` |
| Reset password (API ready) | POST | `auth/reset-password` | `{email,token,password,password_confirmation}` |
| Profile save | PUT | `auth/profile` | name/email/phone/password(+confirmation) |
| Home dashboard | GET | `dashboard` | stats, incoming_bookings, active_operations |
| Accept booking | PATCH | `bookings/{id}` | `{status:"in_progress"}` |
| Reject booking | PATCH | `bookings/{id}` | `{status:"cancelled", notes}` required |
| Complete booking | PATCH | `bookings/{id}` | `{status:"completed"}` |
| Operations list | GET | `operations` | `status,q,from,to,per_page` |
| Lookup QR/NFC/Plate | POST | `operations/lookup` | `{method,value}` |
| Create operation(s) | POST | `operations` | `{vehicle_id,service_ids[],payment_mode,notes,source,booking_id?}` |
| Operation detail | GET | `operations/{id}` | |
| Update status/notes | PATCH | `operations/{id}` | `{status?,notes?}` |
| Upload image | POST | `operations/{id}/images` | multipart `image` + `type` before/after |
| Reports | GET | `reports/summary` | `from,to` optional |
| Customers | GET | `customers` | `q,per_page` |
| Customer detail | GET | `customers/{userId}` | user + operations + bookings |
| Complaints list | GET | `complaints` | `status?` |
| Create complaint | POST | `complaints` | `{subject,body,user_id?,operation_id?}` |
| Notifications | GET | `notifications` | items + unread_count |
| Unread count | GET | `notifications/unread-count` | |
| Mark read | POST | `notifications/{id}/read` | |
| Mark all read | POST | `notifications/read-all` | |
| Delete notification | DELETE | `notifications/{id}` | wired in repo |
| Services list | GET | `services` | includes `is_available`, prices |
| Toggle service | PATCH | `services/{serviceId}` | `{is_available:bool}` |

## User resource fields used

`id, name, email, phone, role, role_label, can_view_revenue, center{id,name,type,show_revenue,...}`

## Booking action mapping (D3)

| UI | API status |
|---|---|
| قبول / Accept | `in_progress` |
| رفض / Reject | `cancelled` + notes |
| إكمال | `completed` |

## Lookup response handling

Uses `vehicle`, `owner`, `available_services`, `open_booking`, `is_company`, `verification_message` (OTP stub).  
`requires_verification` expected `false` in v1; message shown when company vehicle.

## Pagination

List endpoints return `data` as array + `meta{current_page,last_page,per_page,total}`.  
Flutter unwraps list from `data` (or `data.items` for notifications).

## Error handling

Dio exceptions surface `message` from envelope; UI shows SnackBar / `AppErrorView`.
