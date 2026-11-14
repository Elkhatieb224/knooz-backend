# تقرير الوضع الحالي – قبل إعادة الهيكلة

تاريخ التحليل: 2026-11-10  
المشروع: `d:\all_knooz` (Laravel 12)

## 1. البنية الحالية

| الجزء | الحالة |
|-------|--------|
| Laravel في جذر المستودع | نعم |
| Routes منفصلة admin/company/center | جزئياً |
| تسجيل دخول موحّد `/login` | نعم – مشكلة تنظيمية |
| Guard واحد `web` على جدول `users` | نعم – تداخل جلسات |
| عزل بيانات الشركة/المركز في Controllers | جزئي عبر `company_id`/`center_id` |
| مجلد `mobile` لـ Flutter | غير موجود (قبل هذا التحسين) |
| توثيق معماري | خطط سابقة فقط |

## 2. المصادقة الحالية

- جدول واحد: `users` يحتوي كل الأدوار (إدارة، شركة، مركز، عملاء، موظفين).
- Guard: `web` فقط.
- Middleware: `EnsureAdmin` / `EnsureCompany` / `EnsureCenter` تعتمد على `auth()->user()->role`.
- جلسة واحدة مشتركة: مستخدم شركة نظرياً قد يحتفظ بجلسة ويحاول مسارات أخرى (مرفوض بـ 403 لكن نفس جدول الجلسات/المزود).
- رابط دخول واحد لكل اللوحات.

## 3. اللوحات

| اللوحة | Prefix | الحماية |
|--------|--------|---------|
| Admin | `/admin` | `auth` + `admin` |
| Company | `/company` | `auth` + `company` |
| Center | `/center` | `auth` + `center` |

## 4. قاعدة البيانات (مشتركة)

جداول تشغيلية مشتركة: `companies`, `centers`, `services`, `vehicles`, `bookings`, `operations`, `payments`, `wallets`, `invoices`, `complaints`, `offers`, `packages`, `subscriptions`, `settings`, `notifications`.

علاقات حساسة:
- `centers.manager_id` → `users`
- `users.company_id` / `users.center_id`
- الحجوزات/العمليات ترتبط بـ `user_id` (عملاء) و`center_id`

## 5. المشكلات التنظيمية

1. لا توجد روابط دخول مستقلة لكل لوحة.
2. لا توجد Guards منفصلة → خطر تداخل الجلسات والصلاحيات.
3. حسابات اللوحات مختلطة مع عملاء الموبايل في نفس الجدول.
4. عزل البيانات موجود في بعض Controllers فقط وليس كطبقة Policies/Scopes موحّدة.
5. لا يوجد مجلد `docs/` أو `mobile/` مجهز.
6. Services جيدة نسبياً لكن مرتبطة أحياناً بسياق `auth()` الافتراضي.

## 6. قرار معماري معتمد للتحسين

- الإبقاء على Laravel في الجذر (تجنب كسر Composer/Artisan/Vite).
- إنشاء `mobile/` فارغ لـ Flutter لاحقاً.
- إنشاء جداول مصادقة منفصلة: `admin_users`, `company_users`, `center_users`.
- الإبقاء على `users` لعملاء/موظفي التطبيق (بيانات تشغيلية مشتركة).
- Guards: `admin`, `company`, `center`.
- مسارات دخول: `/admin/login`, `/company/login`, `/center/login`.
- قاعدة بيانات واحدة مع عزل صارم بالعلاقات والـ Middleware والـ Scopes.
