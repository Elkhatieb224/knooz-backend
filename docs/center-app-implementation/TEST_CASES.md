# حالات اختبار — Knooz Center App

## A. Backend `/api/center/v1`

| ID | الحالة | المتوقع |
|---|---|---|
| B-AUTH-01 | Login ببيانات صحيحة | 200 + token + user |
| B-AUTH-02 | Login بكلمة خاطئة | 422/خطأ |
| B-AUTH-03 | Login لمستخدم غير نشط | رفض |
| B-AUTH-04 | Login لمركز غير نشط | رفض |
| B-AUTH-05 | me + logout | بيانات صحيحة + إبطال توكن |
| B-BOOK-01 | قبول حجز | status=`in_progress` |
| B-BOOK-02 | رفض حجز + notes | status=`cancelled` |
| B-OPS-01 | lookup plate/qr/nfc | مركبة + خدمات متاحة |
| B-OPS-02 | إنشاء عمليات متعددة services | عدة صفوف operations |
| B-OPS-03 | تحديث حالة + رفع صورة | نجاح |
| B-ISO-01 | مركز A لا يرى حجز مركز B | 404 |
| B-REV-01 | Staff لا يرى إيرادات | حقول مالية مخفية |
| B-REV-02 | متعاقد يخفي الإيرادات افتراضياً | show_revenue=false |
| B-REV-03 | مدير مملوك يرى صافي بعد العمولة | net revenue |

## B. Flutter

| ID | الحالة | المتوقع |
|---|---|---|
| F-VAL-01 | تحقق نموذج الدخول | رفض فارغ/قصير |
| F-MAP-01 | Accept/Reject mapping | in_progress / cancelled |
| F-REV-01 | RevenueVisibility | إخفاء لـ staff / متعاقد |
| F-ROUTE-01 | استبعاد مسارات موظفي الفروع | emergency/roadside… مستبعدة |
| F-FLOW-01 | Splash→Login→Home | يعمل مع توكن |
| F-FLOW-02 | بدء حجز من الرئيسية | قبول + lookup لوحة → تفاصيل |
| F-FLOW-03 | QR/NFC/Plate → تفاصيل → إنشاء | API حقيقي |
| F-FLOW-04 | Forgot + Reset password | شاشات مربوطة |

## C. منع اختلاط التطبيقات

| ID | الحالة | المتوقع |
|---|---|---|
| X-01 | لا مسارات User App (محفظة/متجر/مركباتي) | غير موجودة في router |
| X-02 | لا مسارات Branch Employees | excludedEmployeeOnly |
| X-03 | توكن عميل لا يمر `center.api` | 401/403 |
