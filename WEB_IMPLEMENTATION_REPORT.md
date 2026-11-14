# تقرير تنفيذ نظام الويب – كنوز (KNOOZ)

> تاريخ التقرير: 2026-11-10  
> المرجع: `info.md` + `WEB_IMPLEMENTATION_PLAN.md` + تصميم Figma

---

## 1. ما تم تنفيذه بالتفصيل

تم بناء **ثلاث لوحات ويب مستقلة** بملفات مخصصة لكل لوحة، مرتبطة بقاعدة البيانات، مع:

- مصادقة للإدارة فقط (أدوار: مدير نظام، مدير تشغيل، محاسب، فني دعم)
- واجهة RTL عربية وفق هوية Figma (برتقالي `#F97316`، خط Cairo، بطاقات وإحصائيات)
- Light / Dark Mode عبر CSS Variables + حفظ في `localStorage` وحقل `users.theme`
- تصميم متجاوب (Sidebar قابل للطي على الشاشات الصغيرة)
- Gates للصلاحيات حسب مصفوفة الخطة
- عمليات CRUD فعلية، بحث/فلترة، ترقيم، رسائل نجاح/فشل، Empty states
- مراجعة التحويلات البنكية عبر Transaction يحدّث الدفع ويضيف رصيداً للمحفظة
- إدارة الباقات والخدمات من الإعدادات، وQR/NFC للعملاء والمركبات

**خارج النطاق (وفق الخطة ولم يُنفَّذ كتطبيق ويب):** تطبيقات الموبايل للعملاء / مسؤولي المراكز / موظفي الفروع.

---

## 2. اللوحات والشاشات المكتملة

### هيكل الملفات المخصص لكل لوحة
| اللوحة | Controllers | Views/Components | Assets | Routes | Middleware |
|--------|-------------|------------------|--------|--------|------------|
| الإدارة | `app/Http/Controllers/Admin` | `views/admin` + `components/admin` | `admin.css/js` | `routes/admin.php` | `EnsureAdmin` |
| الشركات | `app/Http/Controllers/Company` | `views/company` + `components/company` | `company.css/js` | `routes/company.php` | `EnsureCompany` |
| المراكز | `app/Http/Controllers/Center` | `views/center` + `components/center` | `center.css/js` | `routes/center.php` | `EnsureCenter` |

### شاشات لوحة الإدارة
| الشاشة | المسار | الحالة |
|--------|--------|--------|
| تسجيل الدخول | `/login` | مكتمل |
| لوحة الرئيسية | `/admin` | مكتمل |
| المستخدمون الأفراد | `/admin/users` | مكتمل (CRUD + تفعيل + QR/NFC) |
| الشركات | `/admin/companies` | مكتمل (CRUD + ربط مركبات + فواتير للعرض) |
| مراكز الخدمة | `/admin/centers` | مكتمل (مملوك/متعاقد + خدمات وأسعار) |
| الطلبات والشكاوي | `/admin/requests` | مكتمل (حجوزات/عمليات/شكاوى/طوارئ) |
| المحافظ والمدفوعات | `/admin/payments`, `/admin/wallets` | مكتمل |
| المركبات | `/admin/vehicles` | مكتمل |
| التقارير والتحليلات | `/admin/reports` | مكتمل (عرض وفلاتر) |
| العروض والإعلانات | `/admin/offers` | مكتمل |
| الإعدادات | `/admin/settings` | مكتمل (عامة + خدمات + باقات + ملف شخصي) |
| الصلاحيات والأدوار | `/admin/roles` | مكتمل |
| الإشعارات | `/admin/notifications` | مكتمل |

### شاشات لوحة الشركات
| الشاشة | المسار | الحالة |
|--------|--------|--------|
| لوحة الشركة | `/company` | مكتمل |
| المركبات | `/company/vehicles` | مكتمل |
| العمليات | `/company/operations` | مكتمل |
| الفواتير | `/company/invoices` | مكتمل |
| الاشتراك | `/company/subscription` | مكتمل |
| الملف الشخصي | `/company/profile` | مكتمل |

### شاشات لوحة المراكز
| الشاشة | المسار | الحالة |
|--------|--------|--------|
| لوحة المركز | `/center` | مكتمل |
| الحجوزات | `/center/bookings` | مكتمل |
| العمليات | `/center/operations` | مكتمل |
| التقارير | `/center/reports` | مكتمل |
| الملف الشخصي | `/center/profile` | مكتمل |

---

## 3. الوظائف المرتبطة فعلياً بقاعدة البيانات

- إحصائيات Dashboard من جداول المستخدمين/المراكز/الشركات/المدفوعات/الشكاوى
- إنشاء وتعديل وحذف (Soft Delete حيث ينطبق) للكيانات الإدارية
- تحديث حالات الحجوزات والعمليات والشكاوى وطلبات الطوارئ
- اعتماد/رفض المدفوعات مع تحديث المحفظة
- تفعيل/إيقاف الخدمات والباقات والحسابات
- توليد أكواد QR/NFC فريدة
- حفظ تفضيل الثيم للمستخدم
- الإشعارات عبر جدول `notifications`

---

## 4. الجداول والعلاقات

`users`, `companies`, `company_user`, `services`, `centers`, `center_service`, `vehicles`, `packages`, `subscriptions`, `bookings`, `operations`, `operation_images`, `wallets`, `wallet_transactions`, `payments`, `invoices`, `invoice_items`, `complaints`, `emergency_requests`, `offers`, `notifications`, `settings`  
+ جداول Laravel: `sessions`, `cache`, `jobs`, `password_reset_tokens`

العلاقات الرئيسية: User↔Company/Center، Center↔Service (pivot بأسعار)، Vehicle↔User/Company، Booking/Operation↔Service/Center/Vehicle، Wallet↔Transactions، Payment↔User/Company، Invoice↔Company/Items.

---

## 5. الصلاحيات والأدوار (بعد فصل المصادقة)

كل لوحة لها رابط دخول خاص (بدون بوابة مشتركة).

| Guard | جدول | رابط الدخول | حساب تجريبي | كلمة المرور |
|-------|------|-------------|-------------|-------------|
| `admin` | `admin_users` | `/admin/login` | `admin@knooz.test` | `password` |
| `company` | `company_users` | `/company/login` | `company@knooz.test` | `password` |
| `center` | `center_users` | `/center/login` | `center@knooz.test` | `password` |

التفاصيل المعمارية: مجلد `docs/` (ARCHITECTURE, AUTHENTICATION, DATA_ISOLATION, …).  
مجلد Flutter المستقبلي: `mobile/` (فارغ حالياً).

التحكم عبر `EnsureAdmin` middleware + Gates في `AppServiceProvider` (لوحة، مستخدمون، شركات، مراكز، حجوزات، مدفوعات، تقارير، عروض، إعدادات، أدوار، شكاوى، إشعارات).

---

## 6. الاختبارات المنفذة والنتائج

| الاختبار | النتيجة |
|----------|---------|
| `php artisan migrate:fresh --seed` | نجح |
| تسجيل دخول `admin@knooz.test` | 302 → `/admin` |
| صفحات الإدارة الأساسية (13 مسار فهرس) | جميعها HTTP 200 |
| صفحات التفاصيل (شركة/مستخدم/مركز/مركبة/عرض/ملف) | HTTP 200 |
| إنشاء مستخدم فردي من اللوحة | 302 نجاح |
| اعتماد دفعة كمحاسب | نجاح مع رسالة تأكيد |
| بذر الإشعارات | 8 إشعارات |

خادم التطوير المستخدم للاختبار: `php artisan serve --host=127.0.0.1 --port=8000`

---

## 7. أخطاء تم إصلاحها أثناء التنفيذ

- فشل Composer بسبب شهادات SSL ناقصة في PHP WinGet → تثبيت `cacert.pem` وضبط `curl.cainfo` / `openssl.cafile`
- عدم توفر خدمة MySQL المسجّلة محلياً → استخدام SQLite للتطوير مع توثيق إعداد MySQL للإنتاج في `.env`
- نموذج ربط مركبة بالشركة كان في الـ Controller فقط → أُضيف للنموذج في صفحة الشركة مع قائمة مركبات متاحة
- بذر إشعارات فارغ → إضافة إشعارات تجريبية لكل أدوار الإدارة

---

## 8. مهام متبقية / حدود معروفة

| البند | السبب |
|-------|--------|
| تطبيقات الموبايل | خارج نطاق الويب حسب `info.md` |
| تصدير التقارير CSV/PDF | لم يُذكر كمتطلب واجهة إلزامي في Figma؛ التقارير للعرض والفلترة متوفرة |
| إصدار ملف فاتورة قابل للتحميل | عرض الفواتير موجود؛ توليد ملف PDF لم يُطلب صراحة كمحرك منفصل |
| بوابات الدفع الأونلاين الحقيقية | يتطلب مفاتيح مزود دفع؛ تم نمذجة الحالة والطرق دون ربط بوابة إنتاج |
| تشغيل MySQL محلياً | ملفات السيرفر موجودة لكن الخدمة غير مفعّلة على الجهاز؛ الـ Migrations متوافقة مع MySQL |

---

## 9. كيفية التشغيل

```bash
php composer.phar install
copy .env.example .env   # إن لزم، ثم ضبط APP_KEY
php artisan key:generate
# للتطوير الحالي: DB_CONNECTION=sqlite وملف database/database.sqlite
php artisan migrate --seed
php artisan serve
```

ثم افتح: `http://127.0.0.1:8000/login`

**للإنتاج على MySQL:** اضبط في `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=knooz
DB_USERNAME=...
DB_PASSWORD=...
```

ثم `php artisan migrate --seed` (أزل/غيّر كلمات مرور الـ seed قبل الإنتاج).

---

## 10. الخلاصة

نظام الويب الإداري لـ **كنوز** منفَّذ ومربوط بقاعدة البيانات ومطابق لنطاق `info.md` وتصميم Figma المعتمد للوحة التحكم، مع توثيق الخطة في `WEB_IMPLEMENTATION_PLAN.md` وهذا التقرير كمرجع اكتمال التنفيذ.
