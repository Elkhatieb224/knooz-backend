# حالات اختبار المشروع بالكامل – KNOOZ

رمز النتائج: `PASS` | `FAIL` | `BLOCKED` | `NOT TESTED`

## أ) المصادقة والعزل

| ID | الحالة | النتيجة |
|---|---|---|
| A-01 | تسجيل دخول Admin ناجح والوصول للوحة | PASS |
| A-02 | مستخدم شركة لا يدخل Admin | PASS |
| A-03 | مستخدم مركز لا يدخل Company | PASS |
| A-04 | شركة لا ترى مركبة شركة أخرى | PASS |
| A-05 | مركز لا يحدّث حجز مركز آخر | PASS |
| A-06 | استعادة كلمة المرور لكل اللوحات | NOT TESTED |
| A-07 | Session fixation / hijacking | NOT TESTED |

## ب) لوحة الإدارة

| ID | الشاشة/الوظيفة | النتيجة |
|---|---|---|
| AD-01 | Dashboard فتح وتحميل | PASS (مسار/كود) |
| AD-02 | Users CRUD + toggle status | NOT TESTED بالكامل |
| AD-03 | Companies CRUD + attach vehicle | NOT TESTED بالكامل |
| AD-04 | Centers CRUD + sync services | NOT TESTED بالكامل |
| AD-05 | Requests hub تبويبات وتحديث الحالات | PASS جزئي (hooks/notifications) |
| AD-06 | Payments approve/reject | PASS جزئي (خدمة + إشعار) |
| AD-07 | Wallets عرض | NOT TESTED |
| AD-08 | Vehicles CRUD | NOT TESTED |
| AD-09 | Reports | NOT TESTED |
| AD-10 | Offers CRUD | NOT TESTED |
| AD-11 | Settings/Packages/Theme/Profile | NOT TESTED |
| AD-12 | Roles edit | NOT TESTED |
| AD-13 | Notifications dropdown/index/open | PASS |

## ج) لوحة الشركة

| ID | الحالة | النتيجة |
|---|---|---|
| CO-01 | Dashboard | NOT TESTED |
| CO-02 | Vehicles index/show عزل | PASS (show عزل) |
| CO-03 | Operations index | NOT TESTED |
| CO-04 | Invoices index/show | NOT TESTED |
| CO-05 | Subscription | NOT TESTED |
| CO-06 | Profile/Theme | NOT TESTED |
| CO-07 | Notifications | PASS |

## د) لوحة المركز

| ID | الحالة | النتيجة |
|---|---|---|
| CE-01 | Dashboard | NOT TESTED |
| CE-02 | Bookings index/update + إشعار | PASS (update+notify) |
| CE-03 | Operations index/update + إشعار | PASS جزئي (hook موجود) |
| CE-04 | Reports | NOT TESTED |
| CE-05 | Profile/Theme | NOT TESTED |
| CE-06 | Notifications | PASS |

## هـ) الإشعارات والـ API

انظر `NOTIFICATIONS_TEST_CASES.md` — النتيجة الإجمالية: **PASS** للاختبارات المنفذة.

## و) الأمن الشامل

| ID | الحالة | النتيجة |
|---|---|---|
| S-01 | IDOR إشعارات | PASS |
| S-02 | عزل Tenant شركات/مراكز | PASS |
| S-03 | CSRF web forms | PASS (إطار Laravel) |
| S-04 | API throttle | PASS (مضبوط) |
| S-05 | XSS مخزّن في حقول المستخدم | NOT TESTED |
| S-06 | رفع ملفات ضار | NOT TESTED |
| S-07 | SQL Injection | NOT TESTED (Eloquent غالبًا) |
| S-08 | Mass assignment حقول حساسة | NOT TESTED بالكامل |

## ز) الأداء

| ID | الحالة | النتيجة |
|---|---|---|
| P-01 | PHPUnit suite محلي | PASS (14) |
| P-02 | k6 1k concurrent | NOT TESTED |
| P-03 | k6 5k/10k | NOT TESTED |
| P-04 | Soak/Spike/Stress | NOT TESTED |
| P-05 | Queue backlog recovery | NOT TESTED |
| P-06 | Million accounts capacity plan | DOCUMENTED ONLY |

## ح) Regression بعد تعديلات الإشعارات

| ID | الحالة | النتيجة |
|---|---|---|
| R-01 | PanelAuthIsolationTest | PASS |
| R-02 | NotificationSystemTest | PASS |
| R-03 | Home redirect | PASS |
| R-04 | مسارات الإشعارات الجديدة لا تكسر اللوحات | PASS |

## ملخص عددي (صادق)

- PASS: 14 اختباراً آلياً + حالات إشعارات موثّقة أعلاه
- FAIL: 0
- BLOCKED: تكامل FCM الحقيقي (لا مفاتيح)، Flutter UI (لا كود)
- NOT TESTED: أغلب CRUD اليدوي الكامل + اختبارات الحمل

لا تُحوَّل الحالات غير المنفذة إلى PASS.
