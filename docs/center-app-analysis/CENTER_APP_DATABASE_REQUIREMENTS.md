# متطلبات قاعدة البيانات — تطبيق المراكز

## 1. الجداول الحالية ذات الصلة (مؤكدة)

| الجدول | الدور لتطبيق المراكز | كفاية؟ |
|---|---|---|
| `centers` | بيانات المركز | كافية للعرض؛ إدارة من Admin |
| `center_users` | حسابات دخول المركز | كافية للأساسيات؛ بلا أدوار فرعية |
| `center_service` | خدمات وأسعار المركز | كافية للقراءة/التوفر |
| `bookings` | طلبات واردة | كافية مع توضيح الحالات |
| `operations` | العمليات التشغيلية | كافية كنواة؛ قد تحتاج حقول إضافية |
| `operation_images` | توثيق صور | كافٍ هيكلياً |
| `vehicles` | `qr_code`, `nfc_code`, `plate_number`, `company_id` | كافٍ للـ lookup |
| `users` | العملاء + موظفين قديمين | العملاء عبر operations/bookings |
| `wallets` / transactions | الدفع من رصيد العميل | مطلوب عند إنشاء العملية |
| `notifications` (DB notifications) | إشعارات CenterUser | كافية مع panel token |
| `panel_api_tokens` | توكنات الموبايل للوحات | جاهزة شكلياً لـ CenterUser |
| `complaints` | شكاوى | **غير كافية** للمراكز (لا `center_id`) |

لا يُقترح نسخ جداول الحجوزات/العمليات — إعادة الاستخدام هي القاعدة.

---

## 2. تعديلات مقترحة (حسب الأولوية)

### P1 — تشغيل العملية من التطبيق

#### 2.1 `operations` — أعمدة اختيارية مستحسنة
| عمود | نوع | سبب |
|---|---|---|
| `created_by_center_user_id` | FK nullable → center_users | من نفّذ من تطبيق المركز |
| `source` | string/enum: `booking`, `qr`, `nfc`, `manual` | تتبع مصدر الإنشاء |
| `verification_passed_at` | timestamp nullable | توثيق تحقق الشركات |

> ملاحظة: يمكن تأجيل الأعمدة إن خُزّن المصدر في `notes`/جدول audit — لكن الأعمدة أوضح.

#### 2.2 دعم خدمات متعددة في عملية واحدة — **قرار**
حالياً `operations.service_id` مفرد.
خيارات:
- **أ)** إنشاء عدة صفوف `operations` لنفس الزيارة (أبسط مع المخطط الحالي)
- **ب)** جدول `operation_services` pivot (أنظف للمنتجات المتعددة على شاشة واحدة)

التوصية: **أ) قصيرة الأمد** لتفادي migration كبيرة؛ **ب) لاحقاً** إن لزم فاتورة موحّدة.

### P1 — تحقق مركبات الشركات
لا يوجد حقل لكود التحقق في DB.

مقترحات (اختيار واحد):
| الخيار | التنفيذ |
|---|---|
| A | `companies.service_pin` أو `vehicles.service_pin` hashed |
| B | كود لمرة واحدة يُولَّد ويُرسل لمسؤول الشركة |
| C | استخدام كلمة مرور حساب شركة موجود (غير مفضل أمنياً) |

**يحتاج قرار منتج قبل migration.**

### P2 — شكاوى المركز
إضافة إلى `complaints` **أو** جدول جديد:

```
center_id (FK, required for center-originated)
operation_id (nullable)
booking_id (nullable)
source: customer|center|admin
```

بدون ذلك لا يمكن عزل شكاوى المركز.

### P2 — أدوار داخل المركز
`center_users` بلا `role`.
إن كان التطبيق موحّداً لمدير + موظف:
- إضافة `role` enum: `manager|staff|viewer`
- أو جدول `center_user_permissions`

### P3 — نسيت كلمة المرور
`password_reset_tokens` يدعم البريد عامةً؛ التأكد من عملها مع `center_users` أو جدول reset مستقل.

### P3 — فهارس
موجودة جزئياً على `status`. يُستحسن:
- `(center_id, scheduled_at)` على bookings
- `(center_id, created_at)` / `(center_id, status)` على operations
- unique يبقى على `vehicles.qr_code` / `nfc_code` / `plate_number` إن لم يكن مفروضاً بالكامل — **تحقق عند التنفيذ**

---

## 3. علاقات يجب احترامها في API

```
CenterUser N—1 Center
Center N—M Service (center_service)
Booking  — center_id, user_id, vehicle_id, service_id
Operation — center_id, user_id, vehicle_id, service_id, booking_id?, employee_id?(users), images
Vehicle — user_id?, company_id?, qr_code, nfc_code
```

قاعدة العزل: كل قراءة/كتابة لـ Booking/Operation تمر عبر `center_id = auth.center_id`.

---

## 4. ما لا يجب تكراره
- لا جدول `center_bookings` منفصل
- لا نسخ `vehicles` للمركز
- لا محفظة للمركز منفصلة عن عمليات الدفع الحالية إلا بقرار مالي صريح

---

## 5. خلاصة النواقص DB
| البند | الحالة |
|---|---|
| نواة التشغيل | موجودة |
| توكنات الموبايل | بنية موجودة |
| ربط منفّذ العملية بـ center_user | ناقص مستحسن |
| مصدر العملية (qr/nfc/…) | ناقص مستحسن |
| كود تحقق شركات | ناقص + قرار |
| شكاوى مرتبطة بالمركز | ناقص |
| أدوار staff داخل المركز | ناقص + قرار |
| خدمات متعددة لكل عملية | قرار تصميمي |
