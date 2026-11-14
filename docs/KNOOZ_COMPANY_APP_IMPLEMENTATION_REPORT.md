# تقرير تنفيذ تطبيق الشركات — Knooz Company App

تاريخ: 2026-11-10

## الحكم

تم تنفيذ تطبيق Flutter للشركات في `flutter/company` وربطه بـ Backend عبر `/api/company/v1` بنفس حسابات `CompanyUser` المستخدمة في لوحة تحكم الشركات على الويب، مع احترام حالة الشركة والمستخدم والصلاحيات على مستوى الـ API. لا يوجد نظام مستخدمين منفصل للتطبيق.

## الشاشات المنفّذة

| الشاشة | المسار | الربط |
|---|---|---|
| Splash | `/` | استعادة الجلسة إن وُجد Token |
| تسجيل الدخول | `/login` | `POST /auth/login` (بريد أو جوال) |
| نسيت كلمة المرور | `/forgot-password` | `POST /auth/forgot-password` |
| إعادة التعيين | `/reset-password` | `POST /auth/reset-password` |
| الرئيسية | `/home` | `GET /dashboard` + تقارير/مركبات/إشعارات |
| أسطول المركبات | `/fleet` | `GET /vehicles` |
| تفاصيل مركبة + QR | `/fleet/:id` | `GET /vehicles/{id}` |
| المحفظة | `/wallet` | `GET /wallet` |
| الفواتير | `/invoices` | `GET /invoices` |
| تفاصيل فاتورة | `/invoices/:id` | `GET /invoices/{id}` |
| التقارير | `/reports` | `GET /reports/summary` |
| العمليات | `/operations` | `GET /operations` |
| الإشعارات | `/notifications` | مجموعة إشعارات الشركة |
| الملف الشخصي | `/profile` | `GET/PUT /auth/me` و `/auth/profile` |
| الإعدادات | `/settings` | لغة ع/إن + تسجيل خروج |
| الاشتراكات | `/subscriptions` | `GET /subscriptions` |
| المزيد | `/more` | روابط الشاشات أعلاه |

شريط التنقل السفلي: الرئيسية | الأسطول | المحفظة | المزيد.

## الوظائف المربوطة

- تسجيل الدخول/الخروج واستعادة الجلسة بنفس `CompanyUser` + `PanelApiToken` (`panel=company`).
- رفض الدخول عند شركة غير نشطة أو مستخدم غير نشط (API + middleware).
- لوحة إحصائيات: مركبات نشطة، إنفاق الشهر، رصيد المحفظة، آخر العمليات.
- أسطول مع بحث وترقيم صفحات؛ زر «إضافة مركبة» يعرض تنبيهاً بأن الإضافة عبر لوحة الويب (لا يوجد `POST /vehicles` في النظام الحالي).
- محفظة: الرصيد وسجل الحركات.
- فواتير، تقارير ملخص الشهر، عمليات مع فلترة الحالة، اشتراكات، إشعارات (قراءة/حذف).
- تحديث الملف الشخصي (الاسم/الجوال/كلمة المرور).
- معالجة انتهاء الجلسة: اعتراض 401/403 ومسح الجلسة محلياً.

## APIs المستخدمة

الأساس: `http://127.0.0.1:8000/api/company/v1`  
(على محاكي Android يُحوَّل تلقائياً إلى `10.0.2.2`)

- Auth: login, logout, me, profile, forgot-password, reset-password
- dashboard, vehicles, vehicles/{id}, operations
- invoices, invoices/{id}, wallet, reports/summary, subscriptions
- notifications (+ unread-count, read, read-all, destroy, open)

## تعديلات Backend

- إضافة كامل مسار `routes/api/company/v1.php` ومجموعة Controllers/Resources/Requests تحت `Api/Company/V1`.
- `CompanyAuthService` + middleware `EnsureCompanyApi` (`company.api`).
- `CompanyUser` يدعم `CanResetPassword` لإعادة تعيين كلمة المرور عبر broker `company_users`.
- توسيع `DashboardService::stats` بحقول `active_vehicles` و `monthly_spend` (متوافق مع لوحة الويب؛ الحقول الإضافية لا تكسر العرض الحالي).
- اختبارات: `tests/Feature/CompanyApiV1Test.php` — **11/11 ناجحة**.

## التكامل مع لوحة تحكم الشركات

- نفس جداول `companies` / `company_users` ونفس بيانات الدخول المُبذورة (`company@knooz.test` / `password`).
- البيانات المعروضة (مركبات، عمليات، محفظة، فواتير، اشتراك) من نفس المصادر التي تستخدمها واجهات الويب.
- عمليات الكتابة المحدودة في التطبيق (تحديث الملف الشخصي) تنعكس على نفس السجل؛ إدارة المركبات/الشحن تبقى عبر الويب حسب APIs المتاحة.

## التكامل مع لوحة الإدارة

- إيقاف الشركة أو المستخدم من الإدارة يمنع الدخول ويبطل التوكنات عبر middleware.
- تعديل بيانات الشركة من الإدارة يظهر في `auth/me` و `dashboard`.
- لا صلاحيات مستقلة في التطبيق؛ الحماية على الـ Backend عبر `panel.api` + `company.api` + عزل `company_id`.

## نتائج الاختبارات

| الاختبار | النتيجة |
|---|---|
| `php artisan test --filter=CompanyApi` | 11/11 PASS |
| Login API بحساب البذرة | نجاح — شركة النقل السريع |
| dashboard / vehicles / wallet / reports | بيانات حقيقية (رصيد 5000، مركبتان، إنفاق 200) |
| عزل بيانات شركة أخرى | مغطى بالاختبار |
| رفض توكن مركز الخدمة | مغطى بالاختبار |
| `flutter analyze` | No issues |
| `flutter build apk --debug` | نجاح سابقاً أثناء التنفيذ |
| تشغيل على emulator-5554 | PASS — التطبيق يعمل |
| Login UI بنفس حساب الويب | PASS — `company@knooz.test` → الرئيسية |
| Home ببيانات حقيقية | PASS — شركة النقل السريع، رصيد 5000، مركبتان، إنفاق 200 |
| تبويب الأسطول | PASS — ش ر ك 1001 / 1002 |
| تبويب المحفظة | PASS — رصيد 5000 وحالة فارغة للحركات |

## أخطاء أُصلحت أثناء التنفيذ

- توحيد الحسابات عبر API جديد بدلاً من نظام auth منفصل.
- إضافة حقول لوحة التحكم الناقصة للتطبيق (`monthly_spend`, `active_vehicles`).
- اعتراض انتهاء التوكن لتفادي بقاء واجهة «مسجّل» بعد 401.
- زر إضافة مركبة بدون API: سلوك واضح بدل استدعاء وهمي.

## تشغيل سريع

```bash
# Backend
cd backend && php artisan serve

# Flutter Company App
cd flutter/company
flutter run -d emulator-5554
# أو جهاز فعلي:
# flutter run --dart-define=API_BASE_URL=http://<LAN-IP>:8000/api/company/v1
```

حساب تجريبي: `company@knooz.test` / `password` (أو الجوال `0553333333`).

في وضع Debug فقط: الضغط المطوّل على أيقونة المبنى في شاشة الدخول يملأ بيانات البذرة لتسهيل الاختبار.

## مشاكل متبقية

انظر `KNOOZ_REMAINING_TASKS.md` و`KNOOZ_INTEGRATION_AUDIT.md`.
