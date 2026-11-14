# متطلبات APIs — تطبيق المراكز

## اتفاقية مقترحة
- Base: `/api/center/v1`
- Auth: `Authorization: Bearer {token}` عبر `PanelApiToken` مع `panel=center` و`tokenable=CenterUser`
- عزل: `center_id` دائماً من التوكن/المستخدم — **لا يُقبل center_id من الـ body**
- اللغة: JSON، تواريخ ISO-8601، pagination قياسية (`data`, `meta`)
- تمييز صريح عن `/api/v1` الخاص بالعميل

---

## 1. Auth

### POST `/api/center/v1/auth/login`
| | |
|---|---|
| Auth | لا |
| Body | `{ "login": "email|phone", "password": "..." }` |
| Response | `{ token, token_type, user: {id,name,email,phone,center:{id,name,type,status}}, abilities? }` |
| Validation | login required, password required; rate limit |
| Errors | 422 بيانات خاطئة، 403 غير نشط / بلا مركز |
| الشاشات | Login |
| الحالة الحالية | **ناقصة** (ويب فقط) |

### POST `/api/center/v1/auth/logout`
إبطال التوكن الحالي — **ناقصة**

### GET `/api/center/v1/auth/me`
ملف المستخدم + المركز — **ناقصة** (منطق قريب من Profile web)

### PUT `/api/center/v1/auth/profile`
تحديث name/phone/password/avatar/theme — **ناقصة** (منطق ويب جزئي)

### POST `/api/center/v1/auth/forgot-password` + reset
**ناقصة بالكامل** — تحتاج قرار قناة (email/SMS)

---

## 2. Dashboard

### GET `/api/center/v1/dashboard`
| | |
|---|---|
| Response | stats (عمليات اليوم، إيرادات اليوم، قيد التنفيذ، طلبات جديدة…)، `incoming_bookings[]`, `active_operations[]`, `quick_actions` ثابتة |
| مصدر منطق | توسيع `DashboardService` |
| الشاشات | Home |
| الحالة | منطق جزئي → **يحتاج API** |

---

## 3. Bookings

### GET `/api/center/v1/bookings`
Query: `status`, `q`, `from`, `to`, `page`  
Response: paginated bookings مع user/vehicle/service  
الحالة: منطق ويب → **يحتاج API**

### GET `/api/center/v1/bookings/{id}`
تفاصيل حجز داخل المركز فقط — **ناقصة**

### PATCH `/api/center/v1/bookings/{id}`
Body: `{ status, notes? }`  
قواعد انتقال مقترحة:
- `new` → `in_progress` (قبول/بدء)
- `new` → `cancelled` (رفض + notes مستحسن)
- منع القفز غير المنطقي  
الحالة: ويب مشابه → **يحتاج API + سياسات انتقال أوضح**

---

## 4. Operations

### GET `/api/center/v1/operations`
فلترة status/q/date + pagination — منطق ويب → **API ناقص**

### GET `/api/center/v1/operations/{id}`
مع images والخدمات — **ناقص**

### POST `/api/center/v1/operations/lookup`
حلّ هوية قبل الإنشاء:
```json
{ "method": "qr|nfc|plate", "value": "..." }
```
Response: vehicle + owner + company flag + available center services + open booking إن وُجد + requires_verification  
**ناقص بالكامل — Priority 1**

### POST `/api/center/v1/operations`
إنشاء عملية:
```json
{
  "vehicle_id": 1,
  "service_ids": [1,2],
  "booking_id": null,
  "payment_mode": "wallet|subscription|cash|credit",
  "notes": "",
  "verification_code": null
}
```
Response: operation(s) + payment_status + amount  
يجب: خصم محفظة/تحقق اشتراك، ربط employee/center_user، منع التكرار لنفس المركبة الجارية  
**ناقص بالكامل — Priority 1**

### PATCH `/api/center/v1/operations/{id}`
تحديث status/notes + started_at/completed_at — منطق ويب → **API ناقص**

### POST `/api/center/v1/operations/{id}/images`
multipart: `type=before|after`, `image`  
**ناقص — Priority 1 للتشغيل الميداني**

---

## 5. Reports

### GET `/api/center/v1/reports/summary`
Query: `from`, `to`  
KPIs مشابهة لـ ReportController — **API ناقص**

### GET `/api/center/v1/reports/breakdown` (P2)
تفصيل حسب خدمة/يوم — **ناقص**

---

## 6. Customers (مركز-scoped)

### GET `/api/center/v1/customers`
عملاء ظهروا في bookings/operations للمركز (distinct users) + آخر زيارة وعدد العمليات  
**ناقص — Priority 2**

### GET `/api/center/v1/customers/{userId}`
تاريخ عمليات/حجوزات هذا العميل في **هذا المركز فقط**  
**ناقص — Priority 2**

---

## 7. Complaints

### الخيار أ (موصى به بعد تعديل DB)
`GET/POST /api/center/v1/complaints` مع `center_id` إلزامي من التوكن + ربط `operation_id` اختياري

### الخيار ب (بدون تعديل كبير)
رفع شكوى كـ support ticket عبر جدول جديد `center_tickets`

الحالة الحالية: جدول `complaints` غير كافٍ → **يحتاج قرار + API**

---

## 8. Notifications

### GET `/api/center/v1/notifications`
### GET `/api/center/v1/notifications/unread-count`
### POST `/api/center/v1/notifications/{id}/read`
### POST `/api/center/v1/notifications/read-all`
### DELETE `/api/center/v1/notifications/{id}`
### GET `/api/center/v1/notifications/{id}/open` → deep link موبايل آمن

منطق العزل موجود في Notification system للـ panel center (ويب).  
**مطلوب تعريض JSON + destinations للموبايل (center://…)**

---

## 9. Center Services (قراءة / إدارة محدودة)

### GET `/api/center/v1/services`
خدمات المركز من pivot `center_service` (السعر المتاح للتشغيل)

### PATCH `/api/center/v1/services/{serviceId}` (P2/قرار)
تفعيل/إيقاف `is_available` فقط — **لا تعديل أسعار التعاقد** (Admin)

---

## 10. APIs موجودة لكن لجهة أخرى (لا تُستخدم كما هي)

| API | لماذا لا تكفي |
|---|---|
| `/api/v1/auth/*` | عملاء `User` + Sanctum فقط |
| `/api/v1/centers` | كتالوج للعميل |
| `/api/v1/vehicles/{id}/qr` | صاحب المركبة فقط |
| `/api/v1/bookings` | حجوزات العميل |

---

## 11. أخطاء موحّدة مقترحة
| Code | معنى |
|---|---|
| 401 | توكن ناقص/منتهٍ |
| 403 | حساب غير نشط / مركز موقف |
| 404 | مورد ليس لهذا المركز (لا تكشف الوجود عبر مركز آخر) |
| 409 | عملية جارية مكررة / انتقال حالة غير مسموح |
| 422 | validation / رصيد غير كافٍ / كود تحقق خاطئ |
| 429 | rate limit على login/lookup |

---

## 12. ملخص العدد
| الحالة | العدد التقريبي |
|---|---|
| جاهزة كما هي للموبايل | 0 |
| منطق ويب قابل للتحويل لـ API | ~8 |
| يجب إنشاؤها من الصفر | ~12+ (lookup/create/images/customers/complaints/auth mobile…) |
